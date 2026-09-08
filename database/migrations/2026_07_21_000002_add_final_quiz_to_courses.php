<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('final_quiz_enabled')->default(false);
            $table->unsignedTinyInteger('pass_percent')->default(80);
            // null = use every question in the bank.
            $table->unsignedInteger('questions_per_attempt')->nullable();
            // null = unlimited attempts.
            $table->unsignedInteger('final_quiz_max_attempts')->nullable();
            // Per-course certificate background image path.
            $table->string('certificate_template')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'final_quiz_enabled',
                'pass_percent',
                'questions_per_attempt',
                'final_quiz_max_attempts',
                'certificate_template',
            ]);
        });
    }
};
