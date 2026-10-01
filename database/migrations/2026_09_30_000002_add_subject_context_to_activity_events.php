<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_events', function (Blueprint $table) {
            $table->nullableMorphs('subject');
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->index(['type', 'created_at']);
            $table->index(['product_id', 'created_at']);
            $table->index(['course_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_events', function (Blueprint $table) {
            $table->dropIndex(['type', 'created_at']);
            $table->dropIndex(['product_id', 'created_at']);
            $table->dropIndex(['course_id', 'created_at']);
            $table->dropConstrainedForeignId('product_id');
            $table->dropConstrainedForeignId('course_id');
            $table->dropMorphs('subject');
        });
    }
};
