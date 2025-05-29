<?php

namespace App\Http\Controllers;

use App\Helpers\LogHelper;
use App\Models\MfaCode;
use App\Models\TotpRegenerate;
use App\Models\UserRoleOrgModel;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FAQRCode\Google2FA;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Notifications\CustomNotification;
use Illuminate\Http\Request;
use App\Notifications\RegenerateTotp;
use App\Notifications\RequestRegenetrateTotp;
use App\ResponseApi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class MFAController extends Controller
{
    use ResponseApi;

    /**
     * Show QR code to enable 2FA
     */
    public function showQrCode()
    {
        try {
            $user = Auth::user();
            $generateQrCode = $this->generateQrCode($user);

            MfaCode::create([
                'user_id' => $user->id,
                'mfa_code' => $generateQrCode['secret'],
            ]);
            $success = [
                'status' => $user->user_status,
                'qr_code' => $generateQrCode['qrCode'],
                'secret' => $generateQrCode['secret'],
            ];
            return $this->sendResponse($success, 'QR code displayed successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     *  Two Factor verification
     */
    public function verifyTotp(Request $request)
    {
        $validator = Validator::make($request->only('otp'), [
            'otp' => 'required|digits:6',
        ], [
            'otp.required' => 'OTP is required.',
            'otp.digits' => 'OTP must be exactly 6 digits.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        try {
            $google2fa = new Google2FA();
            $user = Auth::user();

            if (!$google2fa->verify($request->input('otp'), $user->mfa_token)) {
                return $this->sendError('Invalid TOTP.');
            }

            $user->update([
                'user_status' => 'verified',
                'first_login' => now(),
            ]);

            $user->load(['roles', 'organizations']);

            $user->token()->delete();
            $token = $user->createCustomToken(config('app.name'), 'verified')->accessToken;


            $orgName = (allAdminRoles($user->id)) ? $user->name : optional($user->organizations->first())->name;
            
            $response = [
                'userid' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->user_status,
                'role' => roleNameReadable(optional($user->roles->first())->name),
                'orgId' => optional($user->organizations->first())->id,
                'orgName' => $orgName,
                'logo' => optional($user->organizations->first())->dark_logo,
                'token' => $token,
            ];

            return $this->sendResponse($response, null);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Generate QR Code
     */
    public function generateQrCode($user)
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        // $user->mfa_token = Crypt::encryptString($secret);
        $qrCode = $google2fa->getQRCodeInline(config('app.name'), $user->email, $secret);
        return [
            'qrCode' => $qrCode,
            'secret' => $secret,
        ];
    }

    /**
     * QR code verification
     */
    public function verifyqr(Request $request)
    {
        try {
            $user = Auth::user();
            $mfa_code_data = MfaCode::where('user_id', $user->id)->first();
            $user->mfa_token = $mfa_code_data->mfa_code;
            $user->save();
            $mfa_code_data->delete();
            return $this->sendResponse(null, 'QR code verified successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function requestRegenerateTotp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|min:10|max:255',
        ], [
            'reason.required' => 'Reason is required.',
            'reason.min' => 'Reason must be at least 10 characters.',
            'reason.max' => 'Reason must not be greater than 255 characters',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        DB::beginTransaction();
        try {
            $user = Auth::user();

            if (isSuperAdmin($user->id)) {
                return $this->sendError("Super Admin's TOTP can't be regenerated");
            }

            $user->user_status = 'invited';
            $user->save();

            $org = UserRoleOrgModel::where('user_id', $user->id)->first();
            $org_name = getOrgName($org->organization_id);

            $admin_email = config('app.admin_email');
            $route = config('custom.frontend_url');

            TotpRegenerate::updateOrInsert(['user_id' => $user->id], ['reason' => $request->reason]);

            $created_at = Carbon::now()->format('d-m-Y H:i:s');

            $mailData = [
                'subject' => 'Request for TOTP Regeneration',
                'bodyText' => 'You have received a request to Regenerate TOTP!<br>' .
                                'Account Information:<br>' .
                                "<b>Username:</b> {$user->name}<br>" .
                                "<b>Email:</b> {$user->email}<br>" .
                                "<b>Organization Name:</b> {$org_name}<br>" .
                                "<b>Requested at:</b> {$created_at}<br>" .
                                "<a href='{$route}' class='email-button'>Login to the application</a>"
            ];

            // \Notification::route('mail', $admin_email)->notify(new RequestRegenetrateTotp($user, $org_name, $route));
            \Notification::route('mail', $admin_email)->notify(new CustomNotification($mailData));
            LogHelper::logAction('Requested', 'Authentication', 'User requested for regenerate TOTP', getRoleId(), $org->organization_id);

            DB::commit();

            return $this->sendResponse(null, 'Request sent to Admin for regenerate your OTP');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function RegenerateTotp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'route' => 'required'
        ], [
            'email.required' => 'Email is required.',
            'route.required' => 'Route is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        DB::beginTransaction();

        try {
            $user = User::where('email', $request->email)->first();

            if (isSuperAdmin($user->id)) {
                return $this->sendError("Super Admin's TOTP can't be regenerated");
            }

            $totp_regenerate = TotpRegenerate::where('user_id', $user->id)->first();

            if (!$totp_regenerate) {
                return $this->sendError('This user not requested to regenerate TOTP');
            }

            $user_data = User::where('email', $request->email)->update([
                'user_status' => 'invited',
                'mfa_token' => null,
            ]);
            $totp_regenerate->delete();
            DB::commit();

            if ($user_data) {
                $route = $request->route;
                $mailData = [
                    'subject' => 'Your Regenerated TOTP',
                    'recipientName' => $user->name,
                    'bodyText' => 'I hope this email finds you well.<br>' .
                                    'We would like to inform you that we have successfully regenerated your TOTP (Time-Based One-Time Password).<br>' .
                                    'This new TOTP will enable you to securely access your account and ensure its continued protection.<br>' .
                                    'Just click the link below to proceed.<br>' .
                                    "<a href='{$route}' class='email-button'>Login to the application</a><br>" .
                                    'We value your trust and are committed to providing a secure and seamless experience for your account access.'
                ];

                // \Notification::route('mail', $request->email)->notify(new RegenerateTotp($user->name, $route));
                \Notification::route('mail', $request->email)->notify(new CustomNotification($mailData));
                LogHelper::logAction('Regenerated', 'Authentication', "User regenrated the TOTP for the '{$request->input('email')}' user", getRoleId(), 1);

                return $this->sendResponse(null, 'OTP regenerated successfully E-mail will sent to user');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage(), null, 500);
        }
    }
}
