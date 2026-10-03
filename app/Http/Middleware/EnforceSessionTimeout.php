<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionTimeout
{
    /**
     * Handle an incoming request and enforce inactivity session lifetime.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        if ($request->routeIs('login', 'login.post', 'logout')) {
            return $next($request);
        }

        $sessionLifetimeMinutes = (int) (SystemSetting::valueFor('session_lifetime') ?: config('session.lifetime', 120));
        if ($sessionLifetimeMinutes <= 0) {
            $sessionLifetimeMinutes = 120;
        }
        $maxIdleSeconds = $sessionLifetimeMinutes * 60;

        $lastActivity = $request->session()->get('last_user_activity');
        $now = time();

        if ($lastActivity && ($now - (int) $lastActivity) >= $maxIdleSeconds) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->ajax() || $request->routeIs('live-status')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan masuk kembali.',
                    'session_expired' => true,
                ], 401);
            }

            return redirect()->route('login')->with('notice', 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan masuk kembali.');
        }

        // Jangan perbarui last_user_activity jika request berasal dari background polling tanpa interaksi pengguna
        $isBackgroundPoll = $request->routeIs('live-status')
            || ($request->ajax() && ($request->is('admin/monitoring*') || $request->is('*/notifikasi*') || $request->is('notifikasi*')));

        $hasUserActivity = $request->hasHeader('X-User-Activity') || ! $isBackgroundPoll;

        if ($hasUserActivity || ! $lastActivity) {
            $request->session()->put('last_user_activity', $now);
        }

        return $next($request);
    }
}
