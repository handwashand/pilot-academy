<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two things that go together: deleted content is hidden rather than removed,
 * and a lesson no longer needs a course.
 *
 * Deleting used to be final, and deleting a course took its lessons, its
 * students' certificates and their attempts with it. Content now gets a
 * deleted_at instead, so someone with the Recycle bin right can bring it back.
 * Users and partners are left out on purpose: a person asking for their data
 * to be erased has to be really erased.
 *
 * lessons.course_id becomes optional so a lesson can be written before it has
 * a course, and so one whose home course is truly removed from the database
 * falls back to no course instead of being removed with it. created_by says
 * whose a course-less lesson is.
 */
return new class extends Migration
{
    private const SOFT_DELETED = [
        'courses',
        'lessons',
        'questions',
        'case_studies',
        'tutorials',
        'webinars',
        'products',
        'media_items',
    ];

    public function up(): void
    {
        foreach (self::SOFT_DELETED as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->softDeletes();
            });
        }

        Schema::table('lessons', function (Blueprint $table): void {
            $table->dropForeign(['course_id']);
        });

        Schema::table('lessons', function (Blueprint $table): void {
            $table->foreignId('course_id')->nullable()->change();
            $table->foreign('course_id')->references('id')->on('courses')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Rolling back makes every hidden record visible again — there is nowhere
     * left to say it was deleted. lessons.course_id stays optional: making it
     * required again would mean deleting the lessons that have no course.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
        });

        foreach (self::SOFT_DELETED as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
