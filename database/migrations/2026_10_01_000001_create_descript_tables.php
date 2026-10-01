<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lesson videos translated by Descript, kept in the app.
 *
 * Every Descript call spends credits and every result it hands back is
 * temporary — a published video's download link expires. So what Descript
 * produces is stored here the moment it arrives, and a translation that exists
 * is never asked for again.
 *
 * Two tables because the work splits that way. A video is sent to Descript
 * once (descript_imports), and every language is then made from that one copy
 * (video_translations) — five languages are one import, not five.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descript_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            // The uploaded file this came from. Replacing a lesson's video
            // stores the new one under a new path, so a new path is a new video.
            $table->string('video_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('source_language', 10);
            $table->string('status')->default('pending');
            $table->uuid('project_id')->nullable();
            $table->uuid('job_id')->nullable();
            $table->unsignedInteger('media_seconds_used')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['lesson_id', 'video_path']);
        });

        Schema::create('video_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('descript_import_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('language', 10);
            // 'transcript' now; 'dub' once dubbing is proven on a real account.
            $table->string('kind')->default('transcript');
            $table->string('status')->default('pending');
            $table->uuid('job_id')->nullable();
            $table->uuid('composition_id')->nullable();
            // The project's compositions before the translation started. Descript
            // does not say which composition it made, so if the name it was asked
            // for does not turn up, the new one is the one not in this list.
            $table->json('compositions_before')->nullable();
            // What Descript said it did, kept for when a result needs explaining.
            $table->text('agent_response')->nullable();
            $table->longText('transcript')->nullable();
            $table->string('subtitle_path')->nullable();
            $table->string('dub_path')->nullable();
            $table->unsignedInteger('ai_credits_used')->default(0);
            $table->text('error')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // The rule the whole feature rests on: one of each, ever.
            $table->unique(['descript_import_id', 'language', 'kind']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_translations');
        Schema::dropIfExists('descript_imports');
    }
};
