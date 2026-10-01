<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The language a case study is written in, like courses and lessons: an author
 * writes in their own language and Translate adds the others. Anything written
 * before this was written in the default language.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_studies', function (Blueprint $table): void {
            $table->string('language', 10)->nullable()->after('title');
        });

        $default = Schema::hasTable('languages')
            ? (DB::table('languages')->where('is_default', true)->value('code') ?? 'en')
            : 'en';

        DB::table('case_studies')->whereNull('language')->update(['language' => $default]);
    }

    public function down(): void
    {
        Schema::table('case_studies', function (Blueprint $table): void {
            $table->dropColumn('language');
        });
    }
};
