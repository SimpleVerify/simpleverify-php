<?php

namespace SimpleVerify\Objects;

class Verification
{
    public function __construct(
        public readonly string $verificationId,
        public readonly string $type,
        public readonly string $destination,
        public readonly string $status,
        public readonly ?string $expiresAt = null,
        public readonly ?string $environment = null,
        public readonly ?string $createdAt = null,
        public readonly ?TestData $test = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            verificationId: $data['verification_id'],
            type: $data['type'],
            destination: $data['destination'],
            status: $data['status'],
            expiresAt: $data['expires_at'] ?? null,
            environment: $data['environment'] ?? null,
            createdAt: $data['created_at'] ?? null,
            test: isset($data['test']) ? TestData::fromArray($data['test']) : null,
        );
    }
}
