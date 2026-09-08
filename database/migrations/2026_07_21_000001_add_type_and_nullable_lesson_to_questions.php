<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // single = one correct option (radio); multiple = full-set match (checkbox).
            $table->string('type')->default('single')->after('prompt');
        });

        // Course-only final questions are not tied to a lesson.
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('lesson_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('lesson_id')->nullable(false)->change();
        });
    }
};
