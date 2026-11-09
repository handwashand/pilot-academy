<?php

namespace App\Services\Llm;

use App\Models\AiProvider;
use App\Models\AiUsage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * One JSON completion for ChatGPT, DeepSeek or Claude. ChatGPT and DeepSeek
 * speak chat completions. Claude speaks the Messages API at its own address.
 * The provider row supplies the address, the token and the model.
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
            $response = $this->provider->provider === AiProvider::CLAUDE
                ? $this->postClaude($system, $user, $maxTokens)
                : $this->postChatCompletions($system, $user, $maxTokens);
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

        if ($this->provider->provider === AiProvider::CLAUDE) {
            $content = $this->claudeText($response->json('content'));
            $promptTokens = (int) $response->json('usage.input_tokens');
            $completionTokens = (int) $response->json('usage.output_tokens');
        } else {
            $content = (string) $response->json('choices.0.message.content');
            $promptTokens = (int) $response->json('usage.prompt_tokens');
            $completionTokens = (int) $response->json('usage.completion_tokens');
        }

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

    private function postChatCompletions(string $system, string $user, int $maxTokens): Response
    {
        return Http::withToken((string) $this->provider->api_key)
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
    }

    private function postClaude(string $system, string $user, int $maxTokens): Response
    {
        return Http::withToken((string) $this->provider->api_key)
            ->withHeaders(['anthropic-version' => '2026-10-21'])
            ->acceptJson()
            ->asJson()
            ->timeout(120)
            ->post($this->provider->endpoint(), [
                'model' => $this->model,
                'max_tokens' => $maxTokens,
                'temperature' => 0.2,
                'system' => $system,
                'messages' => [
                    ['role' => 'user', 'content' => $user],
                ],
                'output_config' => ['effort' => 'low'],
            ]);
    }

    private function claudeText(mixed $blocks): string
    {
        if (! is_array($blocks)) {
            return '';
        }

        $text = '';

        foreach ($blocks as $block) {
            if (is_array($block) && ($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null)) {
                $text .= $block['text'];
            }
        }

        return $text;
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
