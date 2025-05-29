<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use App\Models\UserRoleOrgModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\ResponseApi;

class Sq1Middleware
{
    use ResponseApi;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $roleId = Auth::user()->roles[0]->id;
        $userId = Auth::user()->id;

        if ($roleId != RoleNameOrId(null, RoleEnum::Admin) && $roleId != RoleNameOrId(null, RoleEnum::User)) {
            // Check if the authenticated user's role is not sq1_admin or sq1_user
            return $next($request);
        }

        $permCheck = UserRoleOrgModel::where('user_id', $userId)->first();

        if (!$permCheck) {
            return $this->sendError("Your role doesn't have permission to access this request", 403);
        }

        if ($permCheck->role_id == RoleNameOrId(null, RoleEnum::OrgAdmin)) {
            // Check if the authenticated user's role matches the required role
            return $next($request);
        }

        // Return unauthorized response if role does not match
        return $this->sendError("Your role doesn't have permission to access this request", 403);
    }
}
