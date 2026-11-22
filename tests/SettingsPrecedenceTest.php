<?php

declare(strict_types=1);

use G8Scope\Laravel\Settings\G8ScopeSettings;
use Illuminate\Support\Facades\DB;

$env = [
    'bliptrace.endpoint' => 'http://from-env',
    'bliptrace.enabled' => false,
    'bliptrace.environment' => 'staging',
];

function storeSettings(array $values): void
{
    foreach ($values as $name => $value) {
        DB::table('settings')->insert([
            'group' => 'g8scope', 'name' => $name, 'locked' => false, 'payload' => json_encode($value),
        ]);
    }
}

it('ignores the DB settings layer by default, even with spatie installed', function () use ($env): void {
    createSettingsTable();
    storeSettings(['enabled' => true, 'endpoint' => 'http://from-db']);
    rebootSdk($env);

    expect(sdkConfig()->endpoint)->toBe('http://from-env')
        ->and(sdkConfig()->enabled)->toBeFalse()
        ->and(config('settings.settings'))->not->toContain(G8ScopeSettings::class);
});

it('keeps env when the layer is on but the group was never migrated', function () use ($env): void {
    createSettingsTable();
    rebootSdk([...$env, 'bliptrace.settings.enabled' => true]);

    expect(sdkConfig()->endpoint)->toBe('http://from-env')
        ->and(sdkConfig()->enabled)->toBeFalse()
        ->and(sdkConfig()->environment)->toBe('staging')
        ->and(config('settings.settings'))->toContain(G8ScopeSettings::class);
});

it('keeps env when the layer is on but the settings table is missing', function () use ($env): void {
    rebootSdk([...$env, 'bliptrace.settings.enabled' => true]);

    expect(sdkConfig()->endpoint)->toBe('http://from-env');
});

it('uses stored values over env once the group is migrated', function () use ($env): void {
    createSettingsTable();
    storeSettings(['enabled' => true, 'endpoint' => 'http://from-db', 'environment' => '', 'key' => null]);
    rebootSdk([...$env, 'bliptrace.settings.enabled' => true, 'bliptrace.key' => 'env-key']);

    expect(sdkConfig()->endpoint)->toBe('http://from-db')
        ->and(sdkConfig()->enabled)->toBeTrue()
        ->and(sdkConfig()->environment)->toBe('staging')   // empty stored value → env
        ->and(sdkConfig()->key)->toBe('env-key');           // null stored value → env
});

it('passes G8SCOPE_SAMPLE_RATE through to the client', function (): void {
    expect(sdkConfig()->sampleRate)->toBe(1.0);
    rebootSdk(['bliptrace.sample_rate' => '0.25']);
    expect(sdkConfig()->sampleRate)->toBe(0.25);
});
