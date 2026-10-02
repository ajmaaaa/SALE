<?php

use App\Http\Middleware\EnsureAdminProdiAuth;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecurityHeaders::class);
        $middleware->appendToGroup('web', ForcePasswordChange::class);
        $middleware->alias([
            'admin_prodi.auth' => EnsureAdminProdiAuth::class,
            'role' => EnsureRole::class,
            'force_password_change' => ForcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            $message = 'Sesi keamanan telah diperbarui. Silakan ulangi tindakan Anda.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'code' => 'CSRF_TOKEN_EXPIRED',
                ], 419);
            }

            if (! auth()->check()) {
                return redirect()->guest(route('login'))->with('notice', $message);
            }

            return redirect()->back()->withErrors(['session' => $message])->with('notice', $message);
        });
    })
    ->create();
