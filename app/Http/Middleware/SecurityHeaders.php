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

        $isPdfContentType = str_contains(strtolower((string) $response->headers->get('Content-Type', '')), 'application/pdf');
        $isInlineDisposition = str_contains(strtolower((string) $response->headers->get('Content-Disposition', '')), 'inline');
        $pdfPreview = $request->routeIs('preview.file') && $isPdfContentType && $isInlineDisposition;
        $frameAncestors = $pdfPreview ? "'self'" : "'none'";
        $response->headers->set('Content-Security-Policy', "base-uri 'self'; form-action 'self'; object-src 'none'; frame-ancestors {$frameAncestors}");
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', $request->routeIs('preview.file') ? 'cross-origin' : 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), geolocation=(), microphone=()');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', $pdfPreview ? 'SAMEORIGIN' : 'DENY');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if ($request->isSecure() && app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
