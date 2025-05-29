<?php

namespace App\Http\Middleware;

use App\Models\UserRoleOrgModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\ResponseApi;
use Illuminate\Support\Facades\Auth;

class OrgAccess
{
    use ResponseApi;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {


        if (isSuperAdmin(auth()->user()->id)) {
            return $next($request);
        }


        $orgId = $request->route('organization') ?? $request->query('orgId') ?? NULL;

        if(!$orgId) {
            if(allAdminRoles(auth()->user()->id)){
                return $next($request);
            } 
        }

        $dataval = UserRoleOrgModel::where('user_id', auth()->user()->id)->where('organization_id', $orgId)->count();
        if (!$dataval) {
            return $this->sendError("Your role doesn't have permission to access this request", 403);
        }

        return $next($request);
    }
}
