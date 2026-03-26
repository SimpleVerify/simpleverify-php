<?php

namespace SimpleVerify;

use SimpleVerify\Exceptions\ApiException;
use SimpleVerify\Exceptions\AuthenticationException;
use SimpleVerify\Exceptions\ConnectionException;
use SimpleVerify\Exceptions\NotFoundException;
use SimpleVerify\Exceptions\RateLimitException;
use SimpleVerify\Exceptions\ValidationException;

class HttpClient
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;

    public function __construct(string $apiKey, string $baseUrl, int $timeout)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    public function request(string $method, string $path, array $body = []): array
    {
        $url = $this->baseUrl . $path;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'X-API-KEY: ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: simpleverify-php/' . (\Composer\InstalledVersions::getPrettyVersion('simpleverify/simpleverify-php') ?? 'dev'),
            ],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $responseBody = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($curlErrno !== 0) {
            throw new ConnectionException(
                'Failed to connect to SimpleVerify API: ' . $curlError,
            );
        }

        $data = json_decode($responseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException(
                'Invalid JSON response from SimpleVerify API',
                $httpStatus,
            );
        }

        if (($data['status'] ?? '') === 'error') {
            $this->throwException($httpStatus, $data['error'] ?? []);
        }

        return $data['data'] ?? [];
    }

    private function throwException(int $httpStatus, array $error): never
    {
        $message = $error['message'] ?? 'Unknown API error';
        $code = $error['code'] ?? null;
        $details = $error['details'] ?? [];

        throw match ($httpStatus) {
            401 => new AuthenticationException($message, $httpStatus, $code, $details),
            404 => new NotFoundException($message, $httpStatus, $code, $details),
            422 => new ValidationException($message, $httpStatus, $code, $details),
            429 => new RateLimitException($message, $httpStatus, $code, $details),
            default => new ApiException($message, $httpStatus, $code, $details),
        };
    }
}
