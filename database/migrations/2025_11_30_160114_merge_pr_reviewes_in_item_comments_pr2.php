<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BaseComment;
use App\Models\PullRequestReview;

return new class extends Migration
{
    public function up(): void
    {
        // For every pull_request_review, create a corresponding base_comment
        $reviews = PullRequestReview::all();
        foreach ($reviews as $review) {
            $baseComment = BaseComment::create([
                'comment_id' => $review->id,
                'issue_id' => $review->pull_request_id,
                'user_id' => $review->user_id,
                'body' => $review->body,
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
                'type' => 'review',
                'resolved' => $review->resolved,
            ]);

            $review->base_comment_id = $baseComment->id;
            $review->save();
        }
    }

    public function down(): void
    {
        Schema::table('pull_request_reviews', function (Blueprint $table) {
            //
        });
    }
};
