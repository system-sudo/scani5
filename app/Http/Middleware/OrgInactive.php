<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\ResponseApi;

class OrgInactive
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

        if (isSuperAdmin($user->id)) {
            return $next($request);
        }

        $org = $user->organizations->first();

        if ($org->status != 'active') {
            $user = Auth::user();
            $user->token()->delete();
            return $this->sendError('Organization is not active');
        }

        return $next($request);
    }
}
