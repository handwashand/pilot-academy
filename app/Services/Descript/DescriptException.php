<?php

namespace App\Services\Descript;

use RuntimeException;

/**
 * A call to Descript that did not work, carrying enough to tell an editor why:
 * out of credits reads differently from a wrong token.
 */
class DescriptException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly ?string $errorCode = null,
    ) {
        parent::__construct($message, $status);
    }

    /** 402: the plan has run out of media minutes or AI credits. */
    public function isOutOfCredits(): bool
    {
        return $this->status === 402;
    }

    /** 401 / 403: the token is missing, wrong, or cannot reach that drive. */
    public function isAuthProblem(): bool
    {
        return in_array($this->status, [401, 403], true);
    }
}
