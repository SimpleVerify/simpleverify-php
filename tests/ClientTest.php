<?php

namespace SimpleVerify\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SimpleVerify\Client;
use SimpleVerify\Resources\Verifications;

class ClientTest extends TestCase
{
    private const VALID_KEY = 'vk_test_' . 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';

    public function test_accepts_valid_test_key(): void
    {
        $client = new Client(self::VALID_KEY);
        $this->assertInstanceOf(Client::class, $client);
    }

    public function test_accepts_valid_live_key(): void
    {
        $key = 'vk_live_' . str_repeat('ab', 32);
        $client = new Client($key);
        $this->assertInstanceOf(Client::class, $client);
    }

    public function test_accepts_array_config(): void
    {
        $client = new Client([
            'api_key' => self::VALID_KEY,
            'base_url' => 'https://custom.api.com',
            'timeout' => 60,
        ]);
        $this->assertInstanceOf(Client::class, $client);
    }

    public function test_rejects_empty_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('API key is required');
        new Client('');
    }

    public function test_rejects_missing_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('API key is required');
        new Client([]);
    }

    public function test_rejects_invalid_prefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid API key format');
        new Client('vk_bad_' . str_repeat('ab', 32));
    }

    public function test_rejects_short_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid API key format');
        new Client('vk_test_tooshort');
    }

    public function test_verifications_returns_resource(): void
    {
        $mock = new MockHttpClient();
        $client = new Client([
            'api_key' => self::VALID_KEY,
            'http_client' => $mock,
        ]);

        $this->assertInstanceOf(Verifications::class, $client->verifications);
    }

    public function test_verifications_is_same_instance(): void
    {
        $mock = new MockHttpClient();
        $client = new Client([
            'api_key' => self::VALID_KEY,
            'http_client' => $mock,
        ]);

        $this->assertSame($client->verifications, $client->verifications);
    }

    public function test_unknown_resource_throws(): void
    {
        $mock = new MockHttpClient();
        $client = new Client([
            'api_key' => self::VALID_KEY,
            'http_client' => $mock,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown resource: foo');
        $client->foo;
    }
}
