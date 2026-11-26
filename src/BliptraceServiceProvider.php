<?php

declare(strict_types=1);

namespace Bliptrace\Laravel;

use Bliptrace\Laravel\Livewire\SettingsPage;
use Bliptrace\Laravel\Settings\BliptraceSettings;
use Bliptrace\Laravel\Support\Requirements;
use Bliptrace\Php\Client;
use Bliptrace\Php\Config;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelSettings\Factories\SettingsRepositoryFactory;
use Throwable;

class BliptraceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfig();

        // DB-backed settings (opt-in: bliptrace.settings.enabled) — register the
        // settings class + its migrations path so `php artisan migrate` seeds the
        // group. Off by default: the SDK stays on config/env.
        if ($this->dbSettingsEnabled()) {
            $this->app['config']->set('settings.settings', array_merge(
                (array) $this->app['config']->get('settings.settings', []),
                [BliptraceSettings::class],
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
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'bliptrace');

        if ($this->app->runningInConsole()) {
            // Legacy shim: g8scope-* tags are deprecated aliases (removed in 3.0); both publish config/bliptrace.php.
            $config = [__DIR__.'/../config/bliptrace.php' => config_path('bliptrace.php')];
            $this->publishes($config, 'bliptrace-config');
            $this->publishes($config, 'g8scope-config');
            if (Requirements::settingsAvailable()) {
                $migrations = [__DIR__.'/../database/settings' => base_path('database/settings')];
                $this->publishes($migrations, 'bliptrace-settings-migrations');
                $this->publishes($migrations, 'g8scope-settings-migrations');
            }
        }

        $this->hookExceptionHandler();
        $this->registerBreadcrumbs();
        $this->registerUi();
    }

    /**
     * Merge package defaults under `bliptrace`. An app that still has only a
     * published config/g8scope.php (legacy shim, deprecated, removed in 3.0) keeps it: its
     * values are layered over the defaults. A published config/bliptrace.php wins.
     * `g8scope` is mirrored to the result so old config('g8scope.*') reads work.
     */
    private function mergeConfig(): void
    {
        if ($this->app->configurationIsCached()) {
            return;
        }

        $config = $this->app['config'];
        $published = $config->get('bliptrace');
        $legacy = $config->get('g8scope');
        $config->set('bliptrace', (array) $published);

        $this->mergeConfigFrom(__DIR__.'/../config/bliptrace.php', 'bliptrace');

        if (empty($published) && is_array($legacy) && $legacy !== []) {
            $config->set('bliptrace', array_merge($config->get('bliptrace'), $legacy));
        }
        $config->set('g8scope', $config->get('bliptrace'));
    }

    private function dbSettingsEnabled(): bool
    {
        return (bool) $this->app['config']->get('bliptrace.settings.enabled', false)
            && Requirements::settingsAvailable();
    }

    /**
     * Resolve config: values actually stored in the DB settings group (opt-in)
     * override config/env. Read from the repository, not the settings class —
     * spatie fills an unmigrated group with the class defaults, which would
     * silently override BLIPTRACE_* env.
     */
    private function buildConfig(): Config
    {
        $c = (array) $this->app['config']->get('bliptrace', []);

        if ($this->dbSettingsEnabled()) {
            try {
                $stored = SettingsRepositoryFactory::create(BliptraceSettings::repository())
                    ->getPropertiesInGroup(BliptraceSettings::group());
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
        $c['sdk_name'] = 'bliptrace-laravel';

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

    private function registerBreadcrumbs(): void
    {
        $config = $this->app['config'];
        if ($config->get('bliptrace.enabled') && $config->get('bliptrace.max_breadcrumbs', 50) > 0) {
            (new Breadcrumbs($this->app))->register((array) $config->get('bliptrace.breadcrumbs', []));
        }
    }

    /**
     * Register the settings UI route — opt-in (bliptrace.ui.enabled + DB settings),
     * only when the host meets every requirement, and always behind the
     * bliptrace.ui.gate ability (undefined ability = denied).
     */
    private function registerUi(): void
    {
        $ui = (array) $this->app['config']->get('bliptrace.ui', []);

        if (! ($ui['enabled'] ?? false) || ! $this->dbSettingsEnabled() || ! Requirements::uiAvailable($this->app)) {
            return;
        }

        Route::middleware([...($ui['middleware'] ?? ['web', 'auth']), 'can:'.SettingsPage::ability()])
            ->get($ui['path'] ?? 'bliptrace/settings', SettingsPage::class)
            ->name('bliptrace.settings');
    }
}
