<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The all-courses certificate lives in the same table as course certificates,
 * so numbering, the PDF, email, the public check page, revoke and delete all
 * work the same. It has no course and no quiz score; it records which courses
 * it covered on the day it was issued, because more courses may follow.
 *
 * Existing rows are course certificates (the column's default), so nothing
 * about them changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('kind', 20)->default('course')->after('course_id');
            $table->json('covered_course_ids')->nullable()->after('kind');
            $table->unsignedBigInteger('course_id')->nullable()->change();
            $table->unsignedTinyInteger('score_percent')->nullable()->change();
        });
    }

    public function down(): void
    {
        // A course certificate always has a course and a score; the academy rows
        // have neither, so they cannot survive the old shape.
        DB::table('certificates')->where('kind', 'academy')->delete();

        Schema::table('certificates', function (Blueprint $table) {
            $table->unsignedTinyInteger('score_percent')->nullable(false)->change();
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
            $table->dropColumn(['kind', 'covered_course_ids']);
        });
    }
};
