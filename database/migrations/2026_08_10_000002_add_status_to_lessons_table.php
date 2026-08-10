<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give lessons the same draft | published | archived lifecycle as courses.
     * Nothing changes for students at deploy time: every lesson they can see
     * today was is_published = true and becomes "published".
     *
     * Unlike courses, lessons keep defaulting to published — the course is the
     * gate that decides when any of it reaches students.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Matches the old is_published default of true.
            $table->string('status')->default('published')->after('duration_minutes');
        });

        DB::table('lessons')->where('is_published', true)->update(['status' => 'published']);
        DB::table('lessons')->where('is_published', false)->orWhereNull('is_published')->update(['status' => 'draft']);

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->boolean('is_published')->default(true);
        });

        DB::table('lessons')->update(['is_published' => false]);
        DB::table('lessons')->where('status', 'published')->update(['is_published' => true]);

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
