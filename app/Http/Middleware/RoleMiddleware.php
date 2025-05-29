<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;  
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\ResponseApi;
class RoleMiddleware
{
    use ResponseApi;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {

       
        
        // Check if the authenticated user's role matches the required role
        if (Auth::check() && Auth::user()->hasAnyRole($roles)) {
            return $next($request);
        }

        // Return unauthorized response if role does not match

        return $this->sendError("Your role doesn't have permission to access this request", 403);
    }
}
