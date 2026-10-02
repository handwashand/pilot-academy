<?php

namespace App\Services\DeepL;

use RuntimeException;

class DeepLException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly ?string $errorCode = null,
        public readonly ?string $traceId = null,
    ) {
        parent::__construct($message, $status);
    }

    public function isQuotaProblem(): bool
    {
        return $this->status === 456;
    }

    public function isAuthProblem(): bool
    {
        return $this->status === 403;
    }

    public function isTemporary(): bool
    {
        return $this->status === 0
            || in_array($this->status, [429, 500, 503, 504, 529], true);
    }
}
