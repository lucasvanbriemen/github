<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ItemComment;
use App\Models\PullRequestComment;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pull_request_comments', function (Blueprint $table) {
            // For every Pull Request Comment, we need to create a new base comment
            $prComments = PullRequestComment::all();

            foreach ($prComments as $prComment) {
                $baseComment = ItemComment::create([
                    'comment_id' => $prComment->id,
                    'issue_id' => $prComment->pull_request_id,
                    'user_id' => $prComment->user_id,
                    'body' => $prComment->body,
                    'resolved' => $prComment->resolved,
                    'type' => 'code',
                    'created_at' => $prComment->created_at,
                    'updated_at' => $prComment->updated_at
                ]);

                $prComment->base_comment_id = $baseComment->id;
                $prComment->save();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pull_request_comments', function (Blueprint $table) {
            //
        });
    }
};
