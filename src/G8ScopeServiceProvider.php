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
use Spatie\LaravelSettings\Factories\SettingsRepositoryFactory;
use Throwable;

class G8ScopeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/g8scope.php', 'g8scope');

        // DB-backed settings (opt-in: g8scope.settings.enabled) — register the
        // settings class + its migrations path so `php artisan migrate` seeds the
        // group. Off by default: the SDK stays on config/env.
        if ($this->dbSettingsEnabled()) {
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

    private function dbSettingsEnabled(): bool
    {
        return (bool) $this->app['config']->get('g8scope.settings.enabled', false)
            && Requirements::settingsAvailable();
    }

    /**
     * Resolve config: values actually stored in the DB settings group (opt-in)
     * override config/env. Read from the repository, not the settings class —
     * spatie fills an unmigrated group with the class defaults, which would
     * silently override G8SCOPE_* env.
     */
    private function buildConfig(): Config
    {
        $c = (array) $this->app['config']->get('g8scope', []);

        if ($this->dbSettingsEnabled()) {
            try {
                $stored = SettingsRepositoryFactory::create(G8ScopeSettings::repository())
                    ->getPropertiesInGroup(G8ScopeSettings::group());
                foreach (['enabled', 'key', 'endpoint', 'environment', 'release'] as $name) {
                    if (isset($stored[$name]) && $stored[$name] !== '') {
                        $c[$name] = $stored[$name];
                    }
                }
            } catch (Throwable) {
                // Settings table missing / DB down → config/env.
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

    /**
     * Register the settings UI route — opt-in (g8scope.ui.enabled + DB settings),
     * only when the host meets every requirement, and always behind the
     * g8scope.ui.gate ability (undefined ability = denied).
     */
    private function registerUi(): void
    {
        $ui = (array) $this->app['config']->get('g8scope.ui', []);

        if (! ($ui['enabled'] ?? false) || ! $this->dbSettingsEnabled() || ! Requirements::uiAvailable($this->app)) {
            return;
        }

        Route::middleware([...($ui['middleware'] ?? ['web', 'auth']), 'can:'.SettingsPage::ability()])
            ->get($ui['path'] ?? 'g8scope/settings', SettingsPage::class)
            ->name('g8scope.settings');
    }
}
