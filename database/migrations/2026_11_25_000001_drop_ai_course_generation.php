<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The AI course creator (Content → Generate course) was removed on the owner's
 * request, 2026-11-25. Its usage log, revision records and settings go with
 * it, and so does the per-account right to use it. Courses it drafted are
 * ordinary courses and are untouched. AI tokens stay on ai_providers.
 *
 * Rolling back recreates the tables empty: the deleted rows do not come back.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ai_content_revisions');
        Schema::dropIfExists('ai_usages');
        Schema::dropIfExists('ai_generation_settings');

        DB::table('user_permissions')->where('permission', 'ai.generate_course')->delete();
    }

    public function down(): void
    {
        Schema::create('ai_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider');
            $table->string('model');
            $table->string('purpose');
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->decimal('estimated_cost_usd', 12, 6)->nullable();
            $table->timestamps();

            $table->index(['purpose', 'created_at']);
            $table->index(['user_id', 'purpose', 'created_at']);
        });

        Schema::create('ai_content_revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider');
            $table->string('model');
            $table->string('purpose');
            $table->foreignId('ai_usage_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_generation_settings', function (Blueprint $table) {
            $table->id();
            $table->string('default_provider')->nullable();
            $table->boolean('fallback_enabled')->default(false);
            $table->unsignedInteger('daily_generation_limit')->nullable();
            $table->decimal('monthly_budget_usd', 10, 2)->nullable();
            $table->timestamps();
        });
    }
};
