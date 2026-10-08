<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The language a quiz question is written in — needed so Question/Option can
 * use HasContentTranslations the same way Course/Lesson/CaseStudy do. A
 * lesson's own question takes its lesson's language; a course-only question
 * (no lesson_id — see 2026_07_21_000001_add_type_and_nullable_lesson_to_questions.php)
 * has nothing to inherit, so it falls back to the site default, same rule
 * courses/lessons were backfilled with.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->string('language', 10)->nullable()->after('prompt');
        });

        $default = Schema::hasTable('languages')
            ? (DB::table('languages')->where('is_default', true)->value('code') ?? 'en')
            : 'en';

        // A cross-table JOIN-UPDATE is not portable (SQLite, used by the test
        // suite, does not support it the way Postgres does) — a correlated
        // scalar subquery in the SET clause is, and reads the same on both.
        DB::table('questions')
            ->whereNull('language')
            ->whereNotNull('lesson_id')
            ->update(['language' => DB::raw('(select lessons.language from lessons where lessons.id = questions.lesson_id)')]);

        DB::table('questions')->whereNull('language')->update(['language' => $default]);
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropColumn('language');
        });
    }
};
