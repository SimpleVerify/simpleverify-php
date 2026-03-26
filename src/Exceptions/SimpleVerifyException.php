<?php

namespace SimpleVerify\Exceptions;

use Exception;

abstract class SimpleVerifyException extends Exception
{
    public function __construct(
        string $message = '',
        protected ?int $httpStatus = null,
        protected ?string $errorCode = null,
        protected array $details = [],
    ) {
        parent::__construct($message, $httpStatus ?? 0);
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
