<?php

declare(strict_types=1);

namespace G8Scope\Laravel\Tests;

use Flux\FluxServiceProvider;
use G8Scope\Laravel\G8ScopeServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            FluxServiceProvider::class,
            LaravelSettingsServiceProvider::class,
            G8ScopeServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }
}
