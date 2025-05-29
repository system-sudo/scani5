<?php

namespace Tests\Feature\Mfa;

use Tests\Feature\Traits\ApiRequestTrait;
use Tests\TestCase;

class MfaTest extends TestCase
{
    // use ApiRequestTrait;
    protected $baseUrl = '/api/mfa/';

    public function test_show_qrcode(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'qrcode');

        $resp->assertOk()->assertJson(['message' => 'QR code displayed successfully']);
    }

    public function test_verify_qrcode(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'verify/qr-code');

        $resp->assertOk()->assertJson(['message' => 'QR code verified successfully']);
    }

    public function test_verify_totp(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'verify/totp', ['otp' => '12345']);

        $resp->assertOk()->assertJson(['message' => 'Login completed successfully']);
    }

    public function test_request_regenarate_totp(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'request-regenerate/totp', ['reason' => 'test reason']);

        $resp->assertOk()->assertJson(['message' => 'Request sent to Admin for regenerate your OTP']);
    }

    public function test_regenarate_totp(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'regenerate/totp', ['email' => 'udhayakumar.g@sq1.security', 'route' => 'qwqwqw']);

        $resp->assertOk()->assertJson(['message' => 'OTP regenerated successfully E-mail will sent to user']);
    }
}
