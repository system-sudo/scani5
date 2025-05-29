<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Closure;

class SecureHeadersMiddleware
{
    public function handle($request, Closure $next): Response
    {

        $response = $next($request);

        $response->header('Content-Security-Policy', "default-src 'self'");
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Frame-Options', 'SAMEORIGIN');
        $response->header('X-XSS-Protection', '1; mode=block');
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $response;
    }
}
