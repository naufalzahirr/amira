<?php

namespace App\Services\Ai;

use RuntimeException;

class ProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $rateLimited = false,
        public readonly ?int $retryAfter = null,
    ) {
        parent::__construct($message);
    }
}
