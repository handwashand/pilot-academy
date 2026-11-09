<?php

namespace App\Services\Llm;

use App\Models\AiProvider;
use App\Models\AiUsage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * One chat-completions call for ChatGPT or DeepSeek. Both speak the same
 * protocol; the provider row supplies the address, the token and the model.
 *
 * Translation keeps its own client (LlmTranslator). This one is for features
 * that need a JSON object and a usage record. The token and the message text
 * are never logged.
 */
class ChatClient
{
    public function __construct(
        private readonly AiProvider $provider,
        private readonly string $model,
    ) {}

    public function completeJson(string $system, string $user, string $purpose, ?int $userId): ChatReply
    {
        $capabilities = AiProvider::capabilitiesFor($this->provider->provider, $this->model);

        if (! $this->provider->enabled || blank($this->provider->api_key) || ! ($capabilities['json'] ?? false)) {
            throw new LlmException($this->provider->label().' cannot answer this request.', 422, 'unsupported_capability');
        }

        $maxTokens = min(8192, (int) ($capabilities['max_output_tokens'] ?? 4096));

        try {
            $response = Http::withToken((string) $this->provider->api_key)
                ->acceptJson()
                ->asJson()
                ->timeout(120)
                ->post($this->provider->endpoint(), [
                    'model' => $this->model,
                    'temperature' => 0.2,
                    'max_tokens' => $maxTokens,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);
        } catch (ConnectionException) {
            Log::warning('AI provider unreachable', ['provider' => $this->provider->provider]);

            throw new LlmException($this->provider->label().' could not be reached.', 0, 'connection');
        }

        if ($response->failed()) {
            $code = $response->json('error.code') ?? $response->json('error.type');

            Log::warning('AI provider request failed', [
                'provider' => $this->provider->provider,
                'status' => $response->status(),
                'error' => is_string($code) ? $code : null,
            ]);

            throw new LlmException(
                $this->provider->label().' rejected the request.',
                $response->status(),
                is_string($code) ? $code : ($response->status() === 401 ? 'auth' : null),
            );
        }

        $content = (string) $response->json('choices.0.message.content');
        $promptTokens = (int) $response->json('usage.prompt_tokens');
        $completionTokens = (int) $response->json('usage.completion_tokens');
        $reported = $response->json('usage') !== null;

        $usage = AiUsage::query()->create([
            'user_id' => $userId,
            'provider' => $this->provider->provider,
            'model' => $this->model,
            'purpose' => $purpose,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'estimated_cost_usd' => $reported ? $this->estimate($capabilities, $promptTokens, $completionTokens) : null,
        ]);

        return new ChatReply(
            $this->provider->provider,
            $this->model,
            $content,
            $this->decode($content),
            $promptTokens,
            $completionTokens,
            $usage->estimated_cost_usd,
            $usage->id,
        );
    }

    /**
     * @param  array<string, mixed>  $capabilities
     */
    private function estimate(array $capabilities, int $promptTokens, int $completionTokens): string
    {
        $cost = ($promptTokens / 1_000_000) * (float) $capabilities['input_usd_per_million']
            + ($completionTokens / 1_000_000) * (float) $capabilities['output_usd_per_million'];

        return number_format($cost, 6, '.', '');
    }

    /** @return array<string, mixed>|null */
    private function decode(string $content): ?array
    {
        $content = trim($content);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $content, $matches)) {
            $content = $matches[1];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }
}
