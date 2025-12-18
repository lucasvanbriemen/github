<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename item comments to base comments
        Schema::rename('item_comments', 'base_comments');

        // Add the type enum to the comments
        Schema::table('base_comments', function (Blueprint $table) {
            $table->enum('type', ['issue', 'code', 'review'])->default('issue');
        });
    }

    public function down(): void
    {
        Schema::rename('base_comments', 'item_comments');
    }
};
