<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('review', function (Blueprint $table) {
           // We want to backfill existing comments with their review ids if they have one, so we loop over all comments and check for the following criteria:
            // - The comment's author matches the review's author
            // - The comment's created_at timestamp is no more than 5 minutes after the review's created_at timestamp
            $comments = DB::table('pull_request_comments')->get();
            foreach ($comments as $comment) {
                $review = DB::table('pull_request_reviews')
                    ->where('pull_request_id', $comment->pull_request_id)
                    ->where('user_id', $comment->user_id)
                    ->whereBetween('created_at', [
                        date('Y-m-d H:i:s', strtotime($comment->created_at . ' - 5 minutes')),
                        date('Y-m-d H:i:s', strtotime($comment->created_at . ' + 5 minutes')),
                    ])
                    ->first();
                if ($review) {
                    DB::table('pull_request_comments')
                        ->where('id', $comment->id)
                        ->update(['pull_request_review_id' => $review->id]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('review', function (Blueprint $table) {
            //
        });
    }
};
