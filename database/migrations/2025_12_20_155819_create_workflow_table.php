<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('state')->default('queued');
        });

        Schema::create('workflow_jobs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('workflow_id')->constrained('workflows')->onDelete('cascade');
            $table->string('name');
            $table->json('steps');
            $table->string('state')->default('queued');
        });

        Schema::table('commits', function (Blueprint $table) {
            $table->foreignId('workflow_id')->nullable()->constrained('workflows')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflows');
        Schema::dropIfExists('workflow_jobs');

        Schema::table('commits', function (Blueprint $table) {
            $table->dropForeign(['workflow_id']);
            $table->dropColumn('workflow_id');
        });
    }
};
