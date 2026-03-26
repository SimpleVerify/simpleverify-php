<?php

namespace SimpleVerify\Objects;

class VerificationCheck
{
    public function __construct(
        public readonly string $verificationId,
        public readonly bool $valid,
        public readonly ?string $type = null,
        public readonly ?string $destination = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            verificationId: $data['verification_id'],
            valid: $data['valid'],
            type: $data['type'] ?? null,
            destination: $data['destination'] ?? null,
        );
    }
}
