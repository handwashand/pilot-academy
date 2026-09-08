<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A written transcript of the lesson video. It does three jobs: students who
     * cannot use audio can still take the training, everyone else can skim
     * instead of scrubbing, and the words inside a video finally become
     * searchable — until now video content was invisible to the student search.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->text('transcript')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('transcript');
        });
    }
};
