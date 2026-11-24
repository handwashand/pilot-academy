<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data only. Models a provider has retired would make every request to it fail:
 * DeepSeek retired deepseek-chat and deepseek-reasoner on 2026-07-24 (both
 * already ran on its Flash model), and OpenAI no longer offers GPT-4 Turbo or
 * GPT-3.5 Turbo. A saved DeepSeek model moves to deepseek-flash; a retired
 * ChatGPT model is cleared, which means the provider's default model.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('ai_providers')
            ->where('provider', 'deepseek')
            ->whereIn('model', ['deepseek-chat', 'deepseek-reasoner'])
            ->update(['model' => 'deepseek-flash']);

        DB::table('ai_providers')
            ->where('provider', 'chatgpt')
            ->whereIn('model', ['gpt-4-turbo', 'gpt-3.5-turbo'])
            ->update(['model' => null]);
    }

    public function down(): void
    {
        // Nothing to restore: the old names no longer work at the providers.
    }
};
