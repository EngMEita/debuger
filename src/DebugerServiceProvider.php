<?php

namespace Meita\Debuger;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;
use Meita\Debuger\Exceptions\DebugerExceptionHandler;

class DebugerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/debuger.php', 'debuger');

        $this->app->singleton(ExceptionHandler::class, DebugerExceptionHandler::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'debuger');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/debuger.php' => config_path('debuger.php'),
            ], 'debuger-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/debuger'),
            ], 'debuger-views');
        }
    }
}
