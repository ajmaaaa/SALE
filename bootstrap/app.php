<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'dosen.auth' => \App\Http\Middleware\EnsureDosenAuth::class,
            'mahasiswa.auth' => \App\Http\Middleware\EnsureMahasiswaAuth::class,
            'admin.auth' => \App\Http\Middleware\EnsureAdminAuth::class,
            'kaprodi.auth' => \App\Http\Middleware\EnsureKaprodiAuth::class,
            'admin_prodi.auth' => \App\Http\Middleware\EnsureAdminProdiAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
