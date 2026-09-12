<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $pdfPreview = $request->routeIs('preview.file')
            && $response->headers->get('Content-Type') === 'application/pdf'
            && str_starts_with($response->headers->get('Content-Disposition', ''), 'inline;');
        $response->headers->set('Content-Security-Policy', $pdfPreview ? "frame-ancestors 'self'" : "frame-ancestors 'none'");
        $response->headers->set('Permissions-Policy', 'camera=(), geolocation=(), microphone=()');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', $pdfPreview ? 'SAMEORIGIN' : 'DENY');

        return $response;
    }
}
