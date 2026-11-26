<?php

declare(strict_types=1);

use Bliptrace\Laravel\BliptraceServiceProvider;
use Bliptrace\Laravel\Tests\TestCase;
use Bliptrace\Php\Client;
use Bliptrace\Php\Config;

uses(TestCase::class)->in(__DIR__);

/** Re-run the SDK provider after a test changed bliptrace.* config. */
function rebootSdk(array $config = []): void
{
    config()->set($config);
    $provider = new BliptraceServiceProvider(app());
    $provider->register();
    $provider->boot();
    app('router')->getRoutes()->refreshNameLookups(); // routes added after boot
}

/** The Config the container-built Client was given. */
function sdkConfig(): Config
{
    return (fn () => $this->config)->call(app(Client::class));
}

function createSettingsTable(): void
{
    (include __DIR__.'/../vendor/spatie/laravel-settings/database/migrations/create_settings_table.php.stub')->up();
}
