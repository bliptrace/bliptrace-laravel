<?php

declare(strict_types=1);

namespace Bliptrace\Laravel\Tests;

use Bliptrace\Laravel\BliptraceServiceProvider;
use Flux\FluxServiceProvider;
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
            BliptraceServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }
}
