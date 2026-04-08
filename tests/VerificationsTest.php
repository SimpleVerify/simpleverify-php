<?php

namespace SimpleVerify\Tests;

use PHPUnit\Framework\TestCase;
use SimpleVerify\Client;
use SimpleVerify\Objects\MagicLinkExchange;
use SimpleVerify\Objects\Verification;
use SimpleVerify\Objects\VerificationCheck;

class VerificationsTest extends TestCase
{
    private const VALID_KEY = 'vk_test_' . 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';

    private function makeClient(MockHttpClient $mock): Client
    {
        return new Client([
            'api_key' => self::VALID_KEY,
            'http_client' => $mock,
        ]);
    }

    public function test_send_sms_verification(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse([
            'status' => 'success',
            'data' => [
                'verification_id' => 'abc-123',
                'type' => 'sms',
                'destination' => '*******4567',
                'status' => 'pending',
                'expires_at' => '2026-03-25T12:10:00+00:00',
                'environment' => 'test',
                'test' => ['code' => '482913'],
            ],
        ], 201);

        $client = $this->makeClient($mock);
        $result = $client->verifications->send([
            'type' => 'sms',
            'destination' => '+15551234567',
        ]);

        $this->assertInstanceOf(Verification::class, $result);
        $this->assertSame('abc-123', $result->verificationId);
        $this->assertSame('sms', $result->type);
        $this->assertSame('*******4567', $result->destination);
        $this->assertSame('pending', $result->status);
        $this->assertSame('test', $result->environment);
        $this->assertSame('482913', $result->test->code);
        $this->assertNull($result->test->token);

        $req = $mock->getLastRequest();
        $this->assertSame('POST', $req['method']);
        $this->assertSame('/api/v1/verify/send', $req['path']);
        $this->assertSame('sms', $req['body']['type']);
    }

    public function test_send_magic_link(): void
    {
        $token = str_repeat('a', 64);
        $mock = new MockHttpClient();
        $mock->addResponse([
            'status' => 'success',
            'data' => [
                'verification_id' => 'magic-456',
                'type' => 'magic_link',
                'destination' => 'u***@example.com',
                'status' => 'pending',
                'expires_at' => '2026-03-25T12:25:00+00:00',
                'environment' => 'test',
                'test' => ['token' => $token],
            ],
        ], 201);

        $client = $this->makeClient($mock);
        $result = $client->verifications->send([
            'type' => 'magic_link',
            'destination' => 'user@example.com',
            'redirect_url' => 'https://app.com/dashboard',
        ]);

        $this->assertSame('magic_link', $result->type);
        $this->assertSame($token, $result->test->token);
        $this->assertNull($result->test->code);
    }

    public function test_send_live_has_no_test_data(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse([
            'status' => 'success',
            'data' => [
                'verification_id' => 'live-789',
                'type' => 'email',
                'destination' => 'u***@example.com',
                'status' => 'pending',
                'expires_at' => '2026-03-25T12:10:00+00:00',
                'environment' => 'live',
            ],
        ], 201);

        $client = $this->makeClient($mock);
        $result = $client->verifications->send([
            'type' => 'email',
            'destination' => 'user@example.com',
        ]);

        $this->assertSame('live', $result->environment);
        $this->assertNull($result->test);
    }

    public function test_check_valid_code(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse([
            'status' => 'success',
            'data' => [
                'verification_id' => 'abc-123',
                'valid' => true,
                'type' => 'sms',
                'destination' => '*******4567',
            ],
        ]);

        $client = $this->makeClient($mock);
        $result = $client->verifications->check('abc-123', '482913');

        $this->assertInstanceOf(VerificationCheck::class, $result);
        $this->assertTrue($result->valid);
        $this->assertSame('sms', $result->type);
        $this->assertSame('*******4567', $result->destination);

        $req = $mock->getLastRequest();
        $this->assertSame('POST', $req['method']);
        $this->assertSame('/api/v1/verify/check', $req['path']);
        $this->assertSame('abc-123', $req['body']['verification_id']);
        $this->assertSame('482913', $req['body']['code']);
    }

    public function test_check_invalid_code(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse([
            'status' => 'success',
            'data' => [
                'verification_id' => 'abc-123',
                'valid' => false,
            ],
        ]);

        $client = $this->makeClient($mock);
        $result = $client->verifications->check('abc-123', '000000');

        $this->assertFalse($result->valid);
        $this->assertNull($result->type);
        $this->assertNull($result->destination);
    }

    public function test_get_verification_status(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse([
            'status' => 'success',
            'data' => [
                'verification_id' => 'abc-123',
                'type' => 'sms',
                'destination' => '*******4567',
                'status' => 'verified',
                'expires_at' => '2026-03-25T12:10:00+00:00',
                'created_at' => '2026-03-25T12:00:00+00:00',
            ],
        ]);

        $client = $this->makeClient($mock);
        $result = $client->verifications->get('abc-123');

        $this->assertInstanceOf(Verification::class, $result);
        $this->assertSame('verified', $result->status);
        $this->assertSame('2026-03-25T12:00:00+00:00', $result->createdAt);

        $req = $mock->getLastRequest();
        $this->assertSame('GET', $req['method']);
        $this->assertSame('/api/v1/verify/abc-123', $req['path']);
    }

    public function test_exchange_magic_link(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse([
            'status' => 'success',
            'data' => [
                'verification_id' => 'magic-456',
                'type' => 'magic_link',
                'destination' => 'user@example.com',
                'metadata' => ['user_id' => 42],
                'verified_at' => '2026-03-25T12:05:00+00:00',
                'environment' => 'test',
            ],
        ]);

        $client = $this->makeClient($mock);
        $result = $client->verifications->exchange('magic-456', 'exchange-code-123');

        $this->assertInstanceOf(MagicLinkExchange::class, $result);
        $this->assertSame('magic_link', $result->type);
        $this->assertSame('user@example.com', $result->destination);
        $this->assertSame(['user_id' => 42], $result->metadata);

        $req = $mock->getLastRequest();
        $this->assertSame('POST', $req['method']);
        $this->assertSame('/api/v1/verify/exchange', $req['path']);
        $this->assertSame('magic-456', $req['body']['verification_id']);
        $this->assertSame('exchange-code-123', $req['body']['exchange_code']);
    }
}
