<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Usage and the course-generation defaults. Tokens stay on ai_providers.
     * Prompts and answers are not stored: the notes may name a partner.
     */
    public function up(): void
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

    public function down(): void
    {
        Schema::dropIfExists('ai_content_revisions');
        Schema::dropIfExists('ai_usages');
        Schema::dropIfExists('ai_generation_settings');
    }
};
