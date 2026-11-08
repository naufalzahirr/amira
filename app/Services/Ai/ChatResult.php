<?php

namespace App\Services\Ai;

class ChatResult
{
    private function __construct(
        public readonly string $status, // streaming | busy | failed
        public readonly ?ChatStream $stream = null,
        public readonly ?int $retryAfter = null,
        public readonly ?string $error = null,
    ) {}

    public static function streaming(ChatStream $stream): self
    {
        return new self('streaming', stream: $stream);
    }

    public static function busy(int $retryAfter): self
    {
        return new self('busy', retryAfter: $retryAfter);
    }

    public static function failed(string $error): self
    {
        return new self('failed', error: $error);
    }
}
