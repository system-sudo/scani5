<?php

namespace App\Http\Middleware;

use App\Models\AuthCode;
use App\ResponseApi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class MfacheckMiddleware
{
    use ResponseApi;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        $token = $user->token();

        $scopes = $token->scopes;

        if (!in_array('2fa_status_verified', $scopes)) {
            return $this->sendError('MFA not Authenticated', null, 403);
        }

        return $next($request);
    }
}
