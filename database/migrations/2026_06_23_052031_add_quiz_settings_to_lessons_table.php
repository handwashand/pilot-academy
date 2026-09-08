<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->unsignedInteger('quiz_time_limit_minutes')->nullable()->after('content');
            $table->unsignedInteger('quiz_max_attempts')->nullable()->after('quiz_time_limit_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['quiz_time_limit_minutes', 'quiz_max_attempts']);
        });
    }
};
