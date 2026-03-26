<?php

namespace SimpleVerify\Objects;

class TestData
{
    public function __construct(
        public readonly ?string $code = null,
        public readonly ?string $token = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'] ?? null,
            token: $data['token'] ?? null,
        );
    }
}
