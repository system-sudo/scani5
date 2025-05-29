<?php

namespace Tests\Feature\Auth;

use Tests\Feature\Traits\ApiRequestTrait;
use Tests\TestCase;

class AuthTest extends TestCase
{
    // use ApiRequestTrait;
    protected $baseUrl = '/api/auth/';

    // public function test_login(): void
    // {
    //     $resp = $this->postJson($this->baseUrl . 'login', ['email' => 'sq1admin@secqureone.com', 'password' => 'Dev@sq1scani5']);

    //     $resp->assertOk()->assertJson(['message' => 'Login successful, please verify MFA']);
    // }

    // public function test_forgot_password(): void
    // {
    //     $resp = $this->postJson($this->baseUrl . 'forgot-password', ['email' => 'sq1admin@secqureone.com', 'route' => '']);

    //     $resp->assertOk()->assertJson(['message' => 'Forgot password verification link has been sent to your E-mail']);
    // }

    // public function test_verify_forgot_password_link(): void
    // {
    //     $resp = $this->getJson($this->baseUrl . 'forgot-password-verifycheck/fsfsfsfsdfsdsdf');

    //     $resp->assertOk()->assertJson(['message' => 'Success']);
    // }

    public function test_update_password(): void
    {
        $resp = $this->postJson($this->baseUrl . 'update-password', ['token' => 'cfsdsdsds', 'password' => 'password', 'confirm_password' => 'password']);

        $resp = $this->handleResponse($resp);

        $resp->assertOk()->assertJson(['message' => 'Password changed successfully']);
    }

    // public function test_logout(): void
    // {
    //     $resp = $this->postJson($this->baseUrl . 'logout');

    //     $resp->assertOk()->assertJson(['message' => 'Logged out successfully']);
    // }
}
