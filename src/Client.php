<?php

namespace SimpleVerify;

use InvalidArgumentException;
use SimpleVerify\Resources\Verifications;

/**
 * @property-read Verifications $verifications
 */
class Client
{
    private const DEFAULT_BASE_URL = 'https://api.simpleverify.io';
    private const DEFAULT_TIMEOUT = 30;
    private const API_KEY_PATTERN = '/^vk_(test|live)_[0-9a-f]{64}$/';

    private HttpClient $httpClient;
    private ?Verifications $verifications = null;

    public function __construct(string|array $config)
    {
        if (is_string($config)) {
            $config = ['api_key' => $config];
        }

        $apiKey = $config['api_key'] ?? null;

        if (!is_string($apiKey) || $apiKey === '') {
            throw new InvalidArgumentException('API key is required.');
        }

        if (!preg_match(self::API_KEY_PATTERN, $apiKey)) {
            throw new InvalidArgumentException(
                'Invalid API key format. Expected vk_test_ or vk_live_ followed by 64 hex characters.'
            );
        }

        $baseUrl = $config['base_url'] ?? self::DEFAULT_BASE_URL;
        $timeout = $config['timeout'] ?? self::DEFAULT_TIMEOUT;

        $this->httpClient = $config['http_client'] ?? new HttpClient($apiKey, $baseUrl, $timeout);
    }

    public function __get(string $name): mixed
    {
        if ($name === 'verifications') {
            return $this->verifications ??= new Verifications($this->httpClient);
        }

        throw new InvalidArgumentException("Unknown resource: {$name}");
    }
}
