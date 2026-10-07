<?php

namespace App\Services\Llm;

use RuntimeException;

class LlmException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly ?string $errorCode = null,
    ) {
        parent::__construct($message, $status);
    }

    /** Out of quota or balance (OpenAI 429 insufficient_quota, DeepSeek 402). */
    public function isQuotaProblem(): bool
    {
        return $this->status === 402 || $this->errorCode === 'insufficient_quota';
    }

    public function isAuthProblem(): bool
    {
        return in_array($this->status, [401, 403], true);
    }

    public function isTemporary(): bool
    {
        return $this->status === 0 || $this->status === 429 || $this->status >= 500;
    }
}
