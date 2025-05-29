<?php

namespace App\Http\Middleware;

use App\Notifications\UserLockAccount;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\ResponseApi;
use App\Models\User;
use App\Notifications\CustomNotification;

class TrackLoginAttempts
{
    use ResponseApi;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return $this->sendError('Invalid credentials.');
        }

        if (!$user->name) {
            return $this->sendError('Please register your account');
        }

        // Byepass SQ1 Super Admin
        if (isSuperAdmin($user->id)) {
            return $next($request);
        }

        $attempt = DB::table('password_attempts')->where('user_id', $user->id)->first();

        if (Auth::attempt($request->only('email', 'password'))) {
            if ($this->handleSuccessfulLogin($user, $attempt)) {
                return $next($request);
            } else {
                return $this->sendErrorLocked('Your account is locked. Please try again later.');
            }
        } else {
            return $this->handleFailedLogin($user, $attempt);
        }
    }

    protected function handleSuccessfulLogin($user, $attempt)
    {
        return DB::transaction(function () use ($user, $attempt) {
            if ($user->is_locked == 1) {
                if ($attempt && Carbon::parse($attempt->locked_at)->addHours(24)->isPast()) {
                    // Unlock user after 24 hours
                    DB::table('users')->where('id', $user->id)->update(['is_locked' => 0]);
                    DB::table('password_attempts')->where('user_id', $user->id)->delete();
                    return true;
                } else {
                    return false;
                }
            }

            DB::table('password_attempts')->where('user_id', $user->id)->delete();
            return true;
        });
    }

    protected function handleFailedLogin($user, $attempt)
    {
        return DB::transaction(function () use ($user, $attempt) {
            if ($attempt && Carbon::parse($attempt->locked_at)->addHours(24)->isPast()) {
                DB::table('users')->where('id', $user->id)->update(['is_locked' => 0]);
                DB::table('password_attempts')->where('user_id', $user->id)->update([
                    'attempts' => 1,
                    'locked_at' => Carbon::now()
                ]);

                return $this->sendError('Invalid credentials.');
            }

            if ($user->is_locked == 1) {
                return $this->sendErrorLocked('Your account is locked. Please try again later.');
            }

            if ($attempt) {
                if ($attempt->attempts >= 2) {
                    try {
                        DB::table('users')->where('id', $user->id)->update(['is_locked' => 1]);
                        $data = [
                            'subject' => 'Important Notice: Your Account Has Been Temporarily Locked',
                            'recipientName' => $user->name,
                            'bodyText' => 'We regret to inform you that your account has been temporarily locked due to multiple unsuccessful
                                            login attempts. This measure is taken to ensure the security of your account and to protect your personal information.<br>' .
                                           'The account lock is temporary and will automatically be lifted after 24 hours from the time of
                                            the lock. During this period, you will not be able to access your account.<br>' .
                                           'We recommend that you review your login details and ensure that you are using the correct password. If you have forgotten your password, please use the "Forgot Password" feature on our website to reset it.<br>' .
                                           'Thank you for your attention to this matter.'
                        ];
                        // \Notification::route('mail', $user->email)->notify(new UserLockAccount($user->name));
                        \Notification::route('mail', $user->email)->notify(new CustomNotification($data));
                    } catch (\Exception $e) {
                        return $this->sendError($e->getMessage(), null, 500);
                    }

                    DB::table('password_attempts')->where('user_id', $user->id)->update([
                        'attempts' => 3,
                        'locked_at' => Carbon::now()
                    ]);

                    return $this->sendErrorLocked('Your account is locked. Please try after 24 hours.');
                } else {
                    DB::table('password_attempts')->where('user_id', $user->id)->update([
                        'attempts' => $attempt->attempts + 1,
                        'locked_at' => Carbon::now()
                    ]);
                }
            } else {
                DB::table('password_attempts')->insert([
                    'user_id' => $user->id,
                    'attempts' => 1,
                    'locked_at' => Carbon::now()
                ]);
            }

            return $this->sendError('Invalid credentials.');
        });
    }
}
