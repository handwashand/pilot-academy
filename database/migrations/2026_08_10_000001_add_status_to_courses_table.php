<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the courses' publish toggle with a draft | published | archived
     * lifecycle. Nothing changes for students at deploy time: every course they
     * can see today was is_published = true and becomes "published".
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // New courses start as drafts; the backfill below fixes existing rows.
            $table->string('status')->default('draft')->after('audience');
        });

        DB::table('courses')->where('is_published', true)->update(['status' => 'published']);
        DB::table('courses')->where('is_published', false)->orWhereNull('is_published')->update(['status' => 'draft']);

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('is_published')->default(true);
        });

        DB::table('courses')->update(['is_published' => false]);
        DB::table('courses')->where('status', 'published')->update(['is_published' => true]);

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
