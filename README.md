# SimpleVerify PHP SDK

[![Latest Stable Version](https://img.shields.io/packagist/v/simpleverify/simpleverify-php.svg)](https://packagist.org/packages/simpleverify/simpleverify-php)
[![Total Downloads](https://img.shields.io/packagist/dt/simpleverify/simpleverify-php.svg)](https://packagist.org/packages/simpleverify/simpleverify-php)
[![PHP Version Require](https://img.shields.io/packagist/php-v/simpleverify/simpleverify-php.svg)](https://packagist.org/packages/simpleverify/simpleverify-php)
[![License](https://img.shields.io/packagist/l/simpleverify/simpleverify-php.svg)](https://packagist.org/packages/simpleverify/simpleverify-php)
[![Tests](https://github.com/SimpleVerify/simpleverify-php/actions/workflows/tests.yml/badge.svg)](https://github.com/SimpleVerify/simpleverify-php/actions/workflows/tests.yml)

Official PHP client library for the [SimpleVerify](https://simpleverify.io) API. Send and verify SMS codes, email codes, and magic links with a few lines of code.

## Requirements

- PHP 8.1+
- cURL extension
- JSON extension

## Installation

```bash
composer require simpleverify/simpleverify-php
```

## Quick Start

```php
use SimpleVerify\Client;

$client = new Client('vk_test_your_api_key_here');

// Send an SMS verification
$verification = $client->verifications->send([
    'type' => 'sms',
    'destination' => '+15551234567',
]);

echo $verification->verificationId; // "a1b2c3d4-..."
echo $verification->status;          // "pending"

// Check the code the user entered
$result = $client->verifications->check($verification->verificationId, '482913');

if ($result->valid) {
    echo 'Verified!';
}
```

## Usage

### Initialize the Client

```php
// With just an API key
$client = new Client('vk_test_...');

// With options
$client = new Client([
    'api_key'  => 'vk_test_...',
    'base_url' => 'https://api.simpleverify.io', // default
    'timeout'  => 30,                              // default, in seconds
]);

// Static factory
$client = \SimpleVerify\SimpleVerify::make('vk_test_...');
```

### Send a Verification

```php
// SMS
$verification = $client->verifications->send([
    'type' => 'sms',
    'destination' => '+15551234567',
]);

// Email
$verification = $client->verifications->send([
    'type' => 'email',
    'destination' => 'user@example.com',
]);

// Magic link
$verification = $client->verifications->send([
    'type' => 'magic_link',
    'destination' => 'user@example.com',
    'redirect_url' => 'https://yourapp.com/dashboard',
    'failure_redirect_url' => 'https://yourapp.com/auth/magic-link-result',
]);

// With metadata
$verification = $client->verifications->send([
    'type' => 'sms',
    'destination' => '+15551234567',
    'metadata' => ['user_id' => 42],
]);
```

The response is a `Verification` object:

```php
$verification->verificationId; // UUID
$verification->type;           // "sms", "email", or "magic_link"
$verification->destination;    // masked: "*******4567" or "u***@example.com"
$verification->status;         // "pending"
$verification->expiresAt;      // ISO 8601 datetime
$verification->environment;    // "test" or "live"
```

### Test Mode

When using a `vk_test_` API key, the response includes the code or token so you can complete the flow without real SMS/email delivery:

```php
$verification->test->code;  // "482913" (SMS/email)
$verification->test->token; // 64-char string (magic link)
```

In live mode (`vk_live_` key), `$verification->test` is `null`.

If you set `failure_redirect_url` on a magic link, failed clicks redirect there with `status` (`invalid`, `expired`, or `already_used`) and `verification_id` query parameters.

Successful magic link clicks redirect with `status=verified`, `verification_id`, and a one-time `exchange_code`. Redeem that code from your backend:

```php
$exchange = $client->verifications->exchange($verificationId, $exchangeCode);

$exchange->destination; // verified email address
$exchange->metadata;    // original metadata array
```

### Check a Code

```php
$result = $client->verifications->check($verification->verificationId, '482913');

$result->valid;          // true or false
$result->verificationId; // UUID
$result->type;           // present when valid
$result->destination;    // present when valid (masked)
```

An invalid code returns `valid: false` (not an exception). Only check the `valid` field.

### Get Verification Status

```php
$status = $client->verifications->get($verification->verificationId);

$status->status;    // "pending", "verified", or "expired"
$status->createdAt; // ISO 8601 datetime
```

## Error Handling

All API errors throw specific exceptions extending `SimpleVerifyException`:

```php
use SimpleVerify\Exceptions\AuthenticationException;
use SimpleVerify\Exceptions\ValidationException;
use SimpleVerify\Exceptions\RateLimitException;
use SimpleVerify\Exceptions\NotFoundException;
use SimpleVerify\Exceptions\SimpleVerifyException;

try {
    $client->verifications->send([...]);
} catch (RateLimitException $e) {
    $seconds = $e->getRetryAfter();
    echo "Rate limited. Retry in {$seconds} seconds.";
} catch (ValidationException $e) {
    $errors = $e->getDetails();
    // ["destination" => ["Invalid phone number format."]]
} catch (AuthenticationException $e) {
    echo "Bad API key: " . $e->getErrorCode();
} catch (NotFoundException $e) {
    echo "Verification not found.";
} catch (SimpleVerifyException $e) {
    // Catch-all for any API error
    $e->getHttpStatus();  // HTTP status code
    $e->getErrorCode();   // API error code string
    $e->getMessage();     // Human-readable message
    $e->getDetails();     // Additional context array
}
```

| HTTP Status | Exception |
|-------------|-----------|
| 401 | `AuthenticationException` |
| 404 | `NotFoundException` |
| 422 | `ValidationException` |
| 429 | `RateLimitException` |
| Other | `ApiException` |
| Network failure | `ConnectionException` |

## Testing

The client accepts a custom HTTP client for testing. See the `tests/` directory for examples using `MockHttpClient`.

```bash
composer install
./vendor/bin/phpunit
```

## License

MIT
