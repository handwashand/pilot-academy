<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The pattern and colour of the automatic lesson cover. Both nullable: an
 * empty value means dots on Pilot blue (Course::coverThemeFor), so existing
 * courses need no backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('cover_pattern', 20)->nullable()->after('thumbnail');
            $table->string('cover_color', 20)->nullable()->after('cover_pattern');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['cover_pattern', 'cover_color']);
        });
    }
};
