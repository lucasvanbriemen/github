<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\ItemComment;
use App\Models\PullRequestComment;
use App\Models\PullRequestReview;

class CommentRenderer extends Component
{
    public function __construct(
        public $item,
        public $organization,
        public $repository,
        public $pullRequest
    ) {}

    public function render()
    {
        $view = $this->getViewForItem();

        if (!$view) {
            return '';
        }

        return view($view, $this->getViewData());
    }

    private function getViewForItem(): ?string
    {
        return match(true) {
            $this->item instanceof ItemComment => 'repository.pull_requests.partials.issue-comment',
            $this->item instanceof PullRequestComment && !$this->item->in_reply_to_id => 'repository.pull_requests.partials.pr-comment',
            $this->item instanceof PullRequestReview => 'repository.pull_requests.partials.pr-review',
            default => null
        };
    }

    private function getViewData(): array
    {
        $baseData = [
            'organization' => $this->organization,
            'repository' => $this->repository,
            'pullRequest' => $this->pullRequest,
        ];

        return match(true) {
            $this->item instanceof ItemComment => array_merge($baseData, ['comment' => $this->item]),
            $this->item instanceof PullRequestComment => array_merge($baseData, [
                'comment' => $this->item,
                'replies' => $this->item->replies ?? collect()
            ]),
            $this->item instanceof PullRequestReview => array_merge($baseData, ['review' => $this->item]),
            default => $baseData
        };
    }
}