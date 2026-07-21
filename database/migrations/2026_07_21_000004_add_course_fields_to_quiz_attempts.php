<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Set for final-quiz attempts; lesson_id is set for lesson quizzes.
            $table->foreignId('course_id')->nullable()->after('lesson_id')->constrained()->cascadeOnDelete();
            // The randomly selected question ids for this attempt.
            $table->json('question_ids')->nullable()->after('course_id');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->foreignId('lesson_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_id');
            $table->dropColumn('question_ids');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->foreignId('lesson_id')->nullable(false)->change();
        });
    }
};
