<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (class_exists(\Illuminate\Foundation\Console\ServeCommand::class)) {
            \Illuminate\Foundation\Console\ServeCommand::$passthroughVariables[] = 'PHPRC';
            \Illuminate\Foundation\Console\ServeCommand::$passthroughVariables[] = 'LD_LIBRARY_PATH';
        }
        putenv('PHPRC=/home/ajmaaa/.local/etc/php');
        $_ENV['PHPRC'] = '/home/ajmaaa/.local/etc/php';
        $_SERVER['PHPRC'] = '/home/ajmaaa/.local/etc/php';
    }
}
