<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The language a course or lesson is written in. Trainers write in their own
 * language; Translate adds the others. Everything written so far was written
 * in the default language (English).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['courses', 'lessons'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->string('language', 10)->nullable()->after('title');
            });
        }

        $default = Schema::hasTable('languages')
            ? (DB::table('languages')->where('is_default', true)->value('code') ?? 'en')
            : 'en';

        DB::table('courses')->whereNull('language')->update(['language' => $default]);
        DB::table('lessons')->whereNull('language')->update(['language' => $default]);
    }

    public function down(): void
    {
        foreach (['courses', 'lessons'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropColumn('language');
            });
        }
    }
};
