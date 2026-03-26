<?php

namespace SimpleVerify\Resources;

use SimpleVerify\HttpClient;
use SimpleVerify\Objects\Verification;
use SimpleVerify\Objects\VerificationCheck;

class Verifications
{
    public function __construct(private HttpClient $http)
    {
    }

    public function send(array $params): Verification
    {
        $data = $this->http->request('POST', '/v1/verify/send', $params);

        return Verification::fromArray($data);
    }

    public function check(string $verificationId, string $code): VerificationCheck
    {
        $data = $this->http->request('POST', '/v1/verify/check', [
            'verification_id' => $verificationId,
            'code' => $code,
        ]);

        return VerificationCheck::fromArray($data);
    }

    public function get(string $verificationId): Verification
    {
        $data = $this->http->request('GET', '/v1/verify/' . urlencode($verificationId));

        return Verification::fromArray($data);
    }
}
