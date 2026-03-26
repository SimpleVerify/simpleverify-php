<?php

namespace SimpleVerify\Tests;

use PHPUnit\Framework\TestCase;
use SimpleVerify\Objects\TestData;
use SimpleVerify\Objects\Verification;
use SimpleVerify\Objects\VerificationCheck;

class ObjectTest extends TestCase
{
    public function test_verification_from_array(): void
    {
        $v = Verification::fromArray([
            'verification_id' => 'abc-123',
            'type' => 'sms',
            'destination' => '*******4567',
            'status' => 'pending',
            'expires_at' => '2026-03-25T12:10:00+00:00',
            'environment' => 'test',
            'created_at' => '2026-03-25T12:00:00+00:00',
            'test' => ['code' => '123456'],
        ]);

        $this->assertSame('abc-123', $v->verificationId);
        $this->assertSame('sms', $v->type);
        $this->assertSame('*******4567', $v->destination);
        $this->assertSame('pending', $v->status);
        $this->assertSame('2026-03-25T12:10:00+00:00', $v->expiresAt);
        $this->assertSame('test', $v->environment);
        $this->assertSame('2026-03-25T12:00:00+00:00', $v->createdAt);
        $this->assertSame('123456', $v->test->code);
    }

    public function test_verification_without_optional_fields(): void
    {
        $v = Verification::fromArray([
            'verification_id' => 'abc-123',
            'type' => 'email',
            'destination' => 'u***@example.com',
            'status' => 'verified',
        ]);

        $this->assertNull($v->expiresAt);
        $this->assertNull($v->environment);
        $this->assertNull($v->createdAt);
        $this->assertNull($v->test);
    }

    public function test_verification_check_valid(): void
    {
        $vc = VerificationCheck::fromArray([
            'verification_id' => 'abc-123',
            'valid' => true,
            'type' => 'sms',
            'destination' => '*******4567',
        ]);

        $this->assertSame('abc-123', $vc->verificationId);
        $this->assertTrue($vc->valid);
        $this->assertSame('sms', $vc->type);
        $this->assertSame('*******4567', $vc->destination);
    }

    public function test_verification_check_invalid(): void
    {
        $vc = VerificationCheck::fromArray([
            'verification_id' => 'abc-123',
            'valid' => false,
        ]);

        $this->assertFalse($vc->valid);
        $this->assertNull($vc->type);
        $this->assertNull($vc->destination);
    }

    public function test_test_data_with_code(): void
    {
        $td = TestData::fromArray(['code' => '482913']);

        $this->assertSame('482913', $td->code);
        $this->assertNull($td->token);
    }

    public function test_test_data_with_token(): void
    {
        $token = str_repeat('a', 64);
        $td = TestData::fromArray(['token' => $token]);

        $this->assertNull($td->code);
        $this->assertSame($token, $td->token);
    }

    public function test_readonly_properties(): void
    {
        $v = Verification::fromArray([
            'verification_id' => 'abc-123',
            'type' => 'sms',
            'destination' => '*******4567',
            'status' => 'pending',
        ]);

        // Readonly properties should throw on modification
        $this->expectError();
        $v->status = 'verified';
    }
}
