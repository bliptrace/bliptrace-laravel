<?php

declare(strict_types=1);

namespace G8Scope\Laravel;

use G8Scope\Laravel\Livewire\SettingsPage;
use G8Scope\Laravel\Settings\G8ScopeSettings;
use G8Scope\Laravel\Support\Requirements;
use G8Scope\Php\Client;
use G8Scope\Php\Config;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Throwable;

class G8ScopeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/g8scope.php', 'g8scope');

        // DB-backed settings (kickoff-style) — register the settings class + its
        // migrations path so `php artisan migrate` seeds the group. Only when
        // spatie/laravel-settings is installed; otherwise the SDK stays headless.
        if (Requirements::settingsAvailable()) {
            $this->app['config']->set('settings.settings', array_merge(
                (array) $this->app['config']->get('settings.settings', []),
                [G8ScopeSettings::class],
            ));
            $this->app['config']->set('settings.migrations_paths', array_merge(
                (array) $this->app['config']->get('settings.migrations_paths', []),
                [__DIR__.'/../database/settings'],
            ));
        }

        $this->app->singleton(Client::class, fn ($app) => new Client($this->buildConfig()));
        $this->app->singleton(Reporter::class, fn ($app) => new Reporter($app, $app->make(Client::class)));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'g8scope');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/g8scope.php' => config_path('g8scope.php')], 'g8scope-config');
            if (Requirements::settingsAvailable()) {
                $this->publishes([__DIR__.'/../database/settings' => base_path('database/settings')], 'g8scope-settings-migrations');
            }
        }

        $this->hookExceptionHandler();
        $this->registerUi();
    }

    /** Resolve config with precedence: DB settings → config → env defaults. */
    private function buildConfig(): Config
    {
        $c = (array) $this->app['config']->get('g8scope', []);

        if (Requirements::settingsAvailable()) {
            try {
                $s = $this->app->make(G8ScopeSettings::class);
                $c['enabled'] = $s->enabled;
                $c['key'] = $s->key ?: ($c['key'] ?? null);
                $c['endpoint'] = $s->endpoint ?: ($c['endpoint'] ?? 'https://ingest.scope.g8suite.com');
                $c['environment'] = $s->environment ?: ($c['environment'] ?? 'production');
                $c['release'] = $s->release ?: ($c['release'] ?? null);
            } catch (Throwable) {
                // Settings group not migrated yet → fall back to config/env.
            }
        }

        $c['base_path'] = $this->app->basePath();
        $c['sdk_name'] = 'g8scope-laravel';

        return Config::fromArray($c);
    }

    private function hookExceptionHandler(): void
    {
        if (! $this->app->bound(ExceptionHandler::class)) {
            return;
        }
        $handler = $this->app->make(ExceptionHandler::class);
        if (method_exists($handler, 'reportable')) {
            $handler->reportable(function (Throwable $e): void {
                $this->app->make(Reporter::class)->capture($e);
            });
        }
    }

    /** Register the settings UI route — only when the host meets every requirement. */
    private function registerUi(): void
    {
        if (! Requirements::uiAvailable($this->app)) {
            return;
        }

        $ui = (array) $this->app['config']->get('g8scope.ui', []);
        Route::middleware($ui['middleware'] ?? ['web', 'auth'])
            ->get($ui['path'] ?? 'g8scope/settings', SettingsPage::class)
            ->name('g8scope.settings');
    }
}
