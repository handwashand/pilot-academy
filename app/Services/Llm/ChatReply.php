<?php

namespace App\Services\Llm;

/** One chat completion. The text is for the caller; it is not written to the log. */
class ChatReply
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        public readonly string $content,
        public readonly ?array $data,
        public readonly int $promptTokens,
        public readonly int $completionTokens,
        public readonly ?string $estimatedCostUsd,
        public readonly int $usageId,
    ) {}
}
