<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(18));
        Vite::useCspNonce($nonce);
        View::share('cspNonce', $nonce);

        $response = $next($request);

        $isPdfContentType = str_contains(strtolower((string) $response->headers->get('Content-Type', '')), 'application/pdf');
        $isInlineDisposition = str_contains(strtolower((string) $response->headers->get('Content-Disposition', '')), 'inline');
        $pdfPreview = $request->routeIs('preview.file') && $isPdfContentType && $isInlineDisposition;
        $frameAncestors = $pdfPreview ? "'self'" : "'none'";

        $cspHeader = (bool) config('security.csp_report_only', false)
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $isProduction = app()->environment('production');
        $viteDevUrl = '';
        if (file_exists(public_path('hot'))) {
            $viteDevUrl = trim((string) @file_get_contents(public_path('hot')));
        }

        $extraScript = '';
        $extraStyle = '';
        $extraConnect = '';

        if (! $isProduction) {
            $devHosts = array_filter(array_unique([
                $viteDevUrl,
                'http://localhost:5173',
                'http://127.0.0.1:5173',
            ]));

            $wsHosts = array_map(fn ($h) => preg_replace('#^http#', 'ws', $h), $devHosts);

            $devHostsStr = implode(' ', $devHosts);
            $wsHostsStr = implode(' ', $wsHosts);

            $extraScript = ' http:'.($devHostsStr ? ' '.$devHostsStr : '');
            $extraStyle = ' http:'.($devHostsStr ? ' '.$devHostsStr : '');
            $extraConnect = ' http:'.($devHostsStr ? ' '.$devHostsStr : '').($wsHostsStr ? ' '.$wsHostsStr : '');
        }

        $cspDirectives = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "frame-ancestors {$frameAncestors}",
            "script-src 'self' 'nonce-{$nonce}' blob:{$extraScript}",
            "script-src-attr 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline' https:{$extraStyle}",
            "img-src 'self' data: blob: https:".(! $isProduction ? ' http:' : ''),
            "font-src 'self' data: https:".(! $isProduction ? ' http:' : ''),
            "connect-src 'self' blob:{$extraConnect}",
            "worker-src 'self' blob:",
            "media-src 'self' blob: https:".(! $isProduction ? ' http:' : ''),
            "frame-src 'self' blob: data: https://www.youtube-nocookie.com https://www.youtube.com",
        ];

        $response->headers->set($cspHeader, implode('; ', $cspDirectives));
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
