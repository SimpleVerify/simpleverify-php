<?php

namespace SimpleVerify\Tests;

use PHPUnit\Framework\TestCase;
use SimpleVerify\Client;
use SimpleVerify\Exceptions\AuthenticationException;
use SimpleVerify\Exceptions\NotFoundException;
use SimpleVerify\Exceptions\RateLimitException;
use SimpleVerify\Exceptions\ValidationException;

class ExceptionTest extends TestCase
{
    private const VALID_KEY = 'vk_test_' . 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';

    private function makeClient(MockHttpClient $mock): Client
    {
        return new Client([
            'api_key' => self::VALID_KEY,
            'http_client' => $mock,
        ]);
    }

    public function test_authentication_exception(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse([
            'status' => 'error',
            'error' => [
                'code' => 'INVALID_API_KEY',
                'message' => 'The provided API key is not valid.',
            ],
        ], 401);

        $client = $this->makeClient($mock);

        try {
            $client->verifications->send(['type' => 'sms', 'destination' => '+15551234567']);
            $this->fail('Expected AuthenticationException');
        } catch (AuthenticationException $e) {
            $this->assertSame(401, $e->getHttpStatus());
            $this->assertSame('INVALID_API_KEY', $e->getErrorCode());
            $this->assertSame('The provided API key is not valid.', $e->getMessage());
        }
    }

    public function test_validation_exception(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse([
            'status' => 'error',
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'details' => [
                    'destination' => ['Invalid phone number format.'],
                ],
            ],
        ], 422);

        $client = $this->makeClient($mock);

        try {
            $client->verifications->send(['type' => 'sms', 'destination' => 'not-a-phone']);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getHttpStatus());
            $this->assertSame('VALIDATION_ERROR', $e->getErrorCode());
            $this->assertArrayHasKey('destination', $e->getDetails());
        }
    }

    public function test_rate_limit_exception(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse([
            'status' => 'error',
            'error' => [
                'code' => 'RATE_LIMITED',
                'message' => 'Too many verification attempts.',
                'details' => [
                    'retry_after_seconds' => 25,
                ],
            ],
        ], 429);

        $client = $this->makeClient($mock);

        try {
            $client->verifications->send(['type' => 'sms', 'destination' => '+15551234567']);
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->getHttpStatus());
            $this->assertSame('RATE_LIMITED', $e->getErrorCode());
            $this->assertSame(25, $e->getRetryAfter());
        }
    }

    public function test_not_found_exception(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse([
            'status' => 'error',
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Resource not found.',
            ],
        ], 404);

        $client = $this->makeClient($mock);

        try {
            $client->verifications->get('nonexistent-id');
            $this->fail('Expected NotFoundException');
        } catch (NotFoundException $e) {
            $this->assertSame(404, $e->getHttpStatus());
            $this->assertSame('NOT_FOUND', $e->getErrorCode());
        }
    }

    public function test_unsupported_country_is_validation_exception(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse([
            'status' => 'error',
            'error' => [
                'code' => 'UNSUPPORTED_COUNTRY',
                'message' => 'SMS is not currently supported for country: GB',
                'details' => [
                    'country_code' => 'GB',
                    'supported_countries' => ['US', 'CA'],
                ],
            ],
        ], 422);

        $client = $this->makeClient($mock);

        try {
            $client->verifications->send(['type' => 'sms', 'destination' => '+447911123456']);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame('UNSUPPORTED_COUNTRY', $e->getErrorCode());
            $this->assertSame('GB', $e->getDetails()['country_code']);
        }
    }
}
