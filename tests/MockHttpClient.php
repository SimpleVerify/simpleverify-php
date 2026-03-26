<?php

namespace SimpleVerify\Tests;

use SimpleVerify\HttpClient;

class MockHttpClient extends HttpClient
{
    private array $responses = [];
    private array $requests = [];

    public function __construct()
    {
        // Skip parent constructor -- no real cURL needed
    }

    public function addResponse(array $data, int $httpStatus = 200): self
    {
        $this->responses[] = ['data' => $data, 'status' => $httpStatus];

        return $this;
    }

    public function request(string $method, string $path, array $body = []): array
    {
        $this->requests[] = [
            'method' => $method,
            'path' => $path,
            'body' => $body,
        ];

        if (empty($this->responses)) {
            return [];
        }

        $response = array_shift($this->responses);
        $data = $response['data'];
        $httpStatus = $response['status'];

        if (($data['status'] ?? '') === 'error') {
            // Use reflection to access the private throwException method
            $reflection = new \ReflectionMethod(HttpClient::class, 'throwException');
            $reflection->invoke($this, $httpStatus, $data['error'] ?? []);
        }

        return $data['data'] ?? [];
    }

    public function getLastRequest(): ?array
    {
        return end($this->requests) ?: null;
    }

    public function getRequests(): array
    {
        return $this->requests;
    }
}
