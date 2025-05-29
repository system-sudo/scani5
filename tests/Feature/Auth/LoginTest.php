<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PragmaRX\Google2FAQRCode\Google2FA;
use Tests\TestCase;

class LoginTest extends TestCase
{
    /**
      * Test login with valid credentials.
      * @group exclude
      */
    public function test_login_with_valid_credentials(): void
    {
        // if (!auth()->attempt(['email' => 'sq1admin@secqureone.com', 'password' => 'Dev@sq1scani5'])) {
        //     $this->fail('Invalid credentials.');
        // }

        // $user = Auth::user();

        // // dd($user);

        // $org = $user->organizations->first();

        // if (!isOrgActive($org->id)) {
        //     $this->fail('Organization is not active');
        // }

        // $firstToken = $user->createCustomToken(env('APP_NAME'), 'registered')->accessToken;

        // Log::channel('query_log')->info('22222222222222222222222222');
        // Log::channel('query_log')->info($firstToken);

        // $res = $this->withHeader('Authorization', 'Bearer ' . $firstToken)->getJson('/api/mfa/qrcode');

        // $jsonString = $res->getContent();
        // $data = json_decode($jsonString, true);

        // $secret = $data['data']['secret'];
        // Log::channel('query_log')->info($secret);

        // // Generate OTP from the secret key
        // $google2fa = new Google2FA();
        // $otp = $google2fa->getCurrentOtp($secret);

        // Log::channel('query_log')->info($otp);

        // // dd($otp);

        // $response = $this->withHeader('Authorization', 'Bearer ' . $firstToken)->postJson('/api/mfa/verify/totp', ['otp' => $otp]);

        // // dd($response);

        // $json = $response->getContent();
        // $totpdata = json_decode($json, true);

        // $secondToken = $totpdata['data']['token'];

        // Log::channel('query_log')->info('33333333333333333333333333333');
        // Log::channel('query_log')->info($secondToken);

        // dd($response);

        // $resp = $this->withHeader('Authorization', 'Bearer ' . $secondToken)->getJson('/api/assets/assets-details/10');
        // dd($resp);
    }
}
