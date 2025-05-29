<?php

namespace App\Http\Controllers;

use App\Models\PasswordReset;
use App\Models\UserRoleOrgModel;
use App\Rules\Recaptcha;
use Illuminate\Support\Str;
use App\Notifications\ForgotPassword;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Notifications\CustomNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\ResponseApi;

class AuthController extends Controller
{
    use ResponseApi;
    /**
     * User register/ invite
     */

    /**
     * Login
     */
    public function login(Request $request)
    {
        $captchaOnOff = config('custom.captcha_on_off');

        $appName = config('app.name');

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'g-recaptcha-response' => [$captchaOnOff === 'ON' ? 'required' : '', new Recaptcha()],
            // 'g-recaptcha-response' => ['required', new Recaptcha()],
        ], [
            'email.required' => 'Email is required.',
            'password.required' => 'Password is required.',
            'g-recaptcha-response.required' => 'g-recaptcha-response is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        try {
            if (!auth()->attempt(['email' => $request->email, 'password' => $request->password])) {
                return $this->sendError('Invalid credentials.');
            }

            $user = Auth::user();

            if(!allAdminRoles($user->id)) {
                $org = $user->organizations->first();
    
                if (!isOrgActive($org->id)) {
                    return $this->sendError('Organization is not active');
                }
            }

            $token = $user->createCustomToken($appName, 'registered')->accessToken;

            $cookie = cookie('auth_token', $token, 60, '/', null, true, true);

            $response['status'] = $user->mfa_token ? 'mfa_otp' : 'mfa_qr';
            $response['token'] = $token;
            $response['role'] = roleNameReadable(UserRoleOrgModel::where('user_id', $user->id)->first()->roles->name);
            $response['login_status'] = $user->first_login;

            return $this->sendResponse($response, null);
            // return response()->json([
            //     'success' => true,
            //     'message' => 'Login successful, please verify MFA',
            //     'data' => $response
            // ])->withCookie($cookie);
        } catch (\Exception $e) {
            // return $this->sendError('Invalid credentials.', null, 500);
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Forget password
     */
    public function forgotPassword(Request $request)
    {
        $captchaOnOff = config('custom.captcha_on_off');

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'route' => 'required',
            'g-recaptcha-response' => [$captchaOnOff === 'ON' ? 'required' : '', new Recaptcha()],
        ], [
            'email.required' => 'Email is required.',
            'route.required' => 'Route is required.',
            'g-recaptcha-response.required' => 'g-recaptcha-response is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->sendResponse(null, 'Forgot password verification link has been sent to your E-mail');
        }

        $email = $request->email;
        $_token = Str::random(30);
        $route = $request->route . '/' . $_token;

        $password_change = PasswordReset::updateOrInsert(['email' => $email], [
            'token' => $_token,
            'expires_at' => Carbon::now()->addDay(),
        ]);
        $response = [
            'status' => 'Success',
        ];
        $mailData = [
            'subject' => 'Password Reset Request',
            'route' => $route,
            'resetPwd_flow' => true,
        ];

        if ($password_change) {
            // \Notification::route('mail', $email)->notify(new ForgotPassword($route));
            \Notification::route('mail', $email)->notify(new CustomNotification($mailData));
            return $this->sendResponse($response, 'Forgot password verification link has been sent to your E-mail');
        } else {
            return $this->sendError('Something went wrong. Please try again later');
        }
    }

    /**
     * Reset Password
     */
    public function resetPassword(Request $request)
    {
        $captchaOnOff = config('custom.captcha_on_off');
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
            ],
            'confirm_password' => 'required|same:password',
            'g-recaptcha-response' => [$captchaOnOff === 'ON' ? 'required' : '', new Recaptcha()],
        ], [
            'token.required' => 'Token is required.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.string' => 'Password must be a valid string.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'confirm_password.required' => 'Confirm Password is required.',
            'confirm_password.same' => 'Password & Confirm Password do not match.',
            'g-recaptcha-response.required' => 'g-recaptcha-response is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $data = PasswordReset::where('token', $request->token)->first();

        if (!$data) {
            return $this->sendError('URL expired or Invalid.', null, 404);
        }

        $expiresAt = Carbon::parse($data->expires_at);

        $now = Carbon::parse(now());

        if ($now->gte($expiresAt)) {
            return $this->sendError('The link has expired.');
        }

        $check = User::where('email', $data->email)->update(['password' => Hash::make($request->password)]);

        if ($check) {
            PasswordReset::where('token', $request->token)->delete();

            return $this->sendResponse(null, 'Password changed successfully');
        } else {
            return $this->sendError('Something went wrong. Please try again later');
        }
    }

    /**
     * Verify Link Expiration
     */
    public function verifyForgotPasswordLink($token)
    {
        $data = PasswordReset::where('token', $token)->first();

        if (!$data) {
            return $this->sendError('Invalid token.');
        }

        $expiresAt = Carbon::parse($data->expires_at);

        $now = Carbon::parse(now());

        if ($now->gte($expiresAt)) {
            return $this->sendError('The link has expired.');
        }

        return $this->sendResponse(null, 'Success');
    }

    /**
    * Logout
    */
    public function logout(Request $request)
    {
        $user = Auth::user();
        $user->token()->delete();

        return $this->sendResponse(null, null);
    }
}
