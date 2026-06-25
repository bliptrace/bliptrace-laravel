<?php

declare(strict_types=1);

namespace G8Scope\Laravel\Support;

use Flux\Flux;
use Illuminate\Contracts\Foundation\Application;
use Livewire\Component;
use Spatie\LaravelSettings\Settings;

/**
 * Gates the optional UI. The SDK always works headless (config/env); the settings
 * page + its storage are only wired when the host app meets every requirement.
 */
final class Requirements
{
    /** spatie/laravel-settings present → DB-backed settings can be used. */
    public static function settingsAvailable(): bool
    {
        return class_exists(Settings::class);
    }

    /** All UI prerequisites: Laravel 11+, Livewire, Flux, spatie/laravel-settings. */
    public static function uiAvailable(?Application $app = null): bool
    {
        $version = $app ? $app->version() : app()->version();

        return version_compare($version, '11.0', '>=')
            && class_exists(Component::class)
            && class_exists(Flux::class)
            && self::settingsAvailable();
    }

    /** @return array<string,bool> Each requirement and whether it is satisfied (for diagnostics). */
    public static function report(?Application $app = null): array
    {
        $version = $app ? $app->version() : app()->version();

        return [
            'laravel_11_plus' => version_compare($version, '11.0', '>='),
            'livewire' => class_exists(Component::class),
            'flux' => class_exists(Flux::class),
            'spatie_settings' => self::settingsAvailable(),
        ];
    }
}
