<?php

namespace SimpleVerify\Objects;

class MagicLinkExchange
{
    public function __construct(
        public readonly string $verificationId,
        public readonly string $type,
        public readonly string $destination,
        public readonly array $metadata = [],
        public readonly ?string $verifiedAt = null,
        public readonly ?string $environment = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            verificationId: $data['verification_id'],
            type: $data['type'],
            destination: $data['destination'],
            metadata: $data['metadata'] ?? [],
            verifiedAt: $data['verified_at'] ?? null,
            environment: $data['environment'] ?? null,
        );
    }
}
