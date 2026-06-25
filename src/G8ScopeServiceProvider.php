<?php

declare(strict_types=1);

namespace G8Scope\Laravel;

use G8Scope\Php\Client;
use G8Scope\Php\Config;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;
use Throwable;

class G8ScopeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/g8scope.php', 'g8scope');

        $this->app->singleton(Client::class, function ($app) {
            $c = (array) $app['config']->get('g8scope', []);
            $c['base_path'] = $app->basePath();
            $c['sdk_name'] = 'g8scope-laravel';

            return new Client(Config::fromArray($c));
        });

        $this->app->singleton(Reporter::class, fn ($app) => new Reporter($app, $app->make(Client::class)));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/g8scope.php' => config_path('g8scope.php')], 'g8scope-config');
        }

        if ($this->app->bound(ExceptionHandler::class)) {
            $handler = $this->app->make(ExceptionHandler::class);
            if (method_exists($handler, 'reportable')) {
                $handler->reportable(function (Throwable $e): void {
                    $this->app->make(Reporter::class)->capture($e);
                });
            }
        }
    }
}
