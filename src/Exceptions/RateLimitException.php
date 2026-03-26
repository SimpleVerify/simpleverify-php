<?php

namespace SimpleVerify\Exceptions;

class RateLimitException extends SimpleVerifyException
{
    private int $retryAfter;

    public function __construct(
        string $message = '',
        ?int $httpStatus = 429,
        ?string $errorCode = null,
        array $details = [],
    ) {
        parent::__construct($message, $httpStatus, $errorCode, $details);
        $this->retryAfter = (int) ($details['retry_after_seconds'] ?? 0);
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
