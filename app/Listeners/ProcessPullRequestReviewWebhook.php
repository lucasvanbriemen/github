<?php

namespace App\Listeners;

use App\Events\PullRequestReviewWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\PullRequestReview;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Models\GithubUser;
use App\Models\Notification;
use App\Models\RequestedReviewer;
use App\GithubConfig;
use App\Models\BaseComment;
use App\Services\ImportanceScoreService;

class ProcessPullRequestReviewWebhook implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PullRequestReviewWebhookReceived $event): bool
    {
        $payload = $event->payload;

        $reviewData = $payload->review;
        $prData = $payload->pull_request;

        $repositoryData = $payload->repository;
        Repository::updateFromWebhook($repositoryData);

        $userData = $reviewData->user;
        GithubUser::updateFromWebhook($userData);

        // Ensure the pull request exists before creating the review
        $pr = PullRequest::updateFromWebhook($prData);

        $baseComment = BaseComment::updateOrCreate(
            ['comment_id' => $reviewData->id, 'type' => 'review'],
            [
            'issue_id' => $prData->id,
            'user_id' => $userData->id,
            'body' => $reviewData->body ?? '',
            'type' => 'review',
            ]
        );

        $review = PullRequestReview::updateOrCreate(
            ['id' => $reviewData->id],
            [
            'base_comment_id' => $baseComment->id,
            'state' => $reviewData->state,
            ]
        );

        // We also need to create/update RequestedReviewer (since thats how we show reviews in the UI sidebar)
        // RequestedReviewer tracks the review state for display purposes and determines if a PR is blocked
        $existingReviewer = RequestedReviewer::where('pull_request_id', $prData->id)
            ->where('user_id', $userData->id)
            ->first();

        $incomingState = strtolower($reviewData->state);
        $stateToStore = $incomingState;
        $updateData = [];

        /**
         * GitHub Review State Management
         *
         * GitHub has two "blocking" (absolute answer) states:
         * - 'approved': PR can be merged
         * - 'changes_requested': PR cannot be merged until this is cleared
         *
         * And two non-blocking states:
         * - 'commented': Reviewer just left a comment, doesn't block
         * - 'dismissed': Review was explicitly dismissed by PR author, becomes pending
         *
         * Key principle: Once a blocking state is set, it should persist through
         * dismissals and comments until explicitly cleared or overwritten.
         *
         * Example flow that we must handle:
         * 1. Reviewer requests changes → state = 'changes_requested'
         * 2. PR author dismisses the review → state = 'pending', last_state_before_dismiss = 'changes_requested'
         * 3. Reviewer comments on dismissed review → state should REVERT to 'changes_requested'
         *    (This is GitHub's behavior - blocking states can't be cleared by just commenting)
         */

        if ($incomingState === 'dismissed') {
            // When a review is dismissed:
            // - Save what state it had before dismissal (could be 'approved' or 'changes_requested')
            // - Set the current state to 'pending' (user is back in the requested_reviewers list)
            // - This allows us to restore the previous blocking state if they comment later
            if ($existingReviewer) {
                $updateData['last_state_before_dismiss'] = $existingReviewer->state;
            }
            $stateToStore = 'pending';
        } elseif ($incomingState === 'commented' && $existingReviewer) {
            // When reviewer comments on their review:
            // Check if they had a blocking state before dismissal
            if (in_array($existingReviewer->last_state_before_dismiss, PullRequestReview::ABSOLUTE_ANSWERS)) {
                // FALLBACK: Restore the blocking state that existed before dismissal
                // This matches GitHub's behavior exactly - you can't clear a blocking review just by commenting
                $stateToStore = $existingReviewer->last_state_before_dismiss;
                // Clear the saved state since we've now restored it
                $updateData['last_state_before_dismiss'] = null;
            } elseif (in_array($existingReviewer->state, PullRequestReview::ABSOLUTE_ANSWERS)) {
                // PRESERVE BLOCK: They currently have a blocking state (not from dismissal)
                // Don't let comments overwrite it - maintain the blocking state
                $stateToStore = $existingReviewer->state;
            } else {
                // NO BLOCK: No blocking states exist, so allow the comment to stand
                $updateData['last_state_before_dismiss'] = null;
            }
        } elseif (in_array($incomingState, PullRequestReview::ABSOLUTE_ANSWERS)) {
            // When submitting a new blocking review (approval or changes_requested):
            // Clear any "saved state from dismissal" since this is a fresh review
            // The new blocking state is what matters now
            $updateData['last_state_before_dismiss'] = null;
        }

        $updateData['state'] = $stateToStore;

        RequestedReviewer::updateOrCreate(
            [
                'pull_request_id' => $prData->id,
                'user_id' => $userData->id,
            ],
            $updateData
        );

        // Recalculate importance score (review status changes affect priority)
        ImportanceScoreService::updateItemScore($pr);

        // Create notification if user is assigned OR is the author of the PR
        if ($pr->isCurrentlyAssignedToUser() || $pr->opened_by_id === GithubConfig::USERID) {
            // Don't create notification if actor is the configured user
            if ($userData->id === GithubConfig::USERID) {
                // Continue processing but skip notification
            } elseif (!Notification::where('type', 'pr_review')
                ->where('related_id', $review->id)
                ->exists()) {
                Notification::create([
                    'type' => 'pr_review',
                    'related_id' => $review->id,
                    'triggered_by_id' => $userData->id,
                ]);
            }
        }

        return true;
    }
}
