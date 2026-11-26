<?php

declare(strict_types=1);

use Bliptrace\Laravel\Settings\BliptraceSettings;
use Illuminate\Support\Facades\DB;

$env = [
    'bliptrace.endpoint' => 'http://from-env',
    'bliptrace.enabled' => false,
    'bliptrace.environment' => 'staging',
];

function storeSettings(array $values, string $group = 'bliptrace'): void
{
    foreach ($values as $name => $value) {
        DB::table('settings')->insert([
            'group' => $group, 'name' => $name, 'locked' => false, 'payload' => json_encode($value),
        ]);
    }
}

it('ignores the DB settings layer by default, even with spatie installed', function () use ($env): void {
    createSettingsTable();
    storeSettings(['enabled' => true, 'endpoint' => 'http://from-db']);
    rebootSdk($env);

    expect(sdkConfig()->endpoint)->toBe('http://from-env')
        ->and(sdkConfig()->enabled)->toBeFalse()
        ->and(config('settings.settings'))->not->toContain(BliptraceSettings::class);
});

it('keeps env when the layer is on but the group was never migrated', function () use ($env): void {
    createSettingsTable();
    rebootSdk([...$env, 'bliptrace.settings.enabled' => true]);

    expect(sdkConfig()->endpoint)->toBe('http://from-env')
        ->and(sdkConfig()->enabled)->toBeFalse()
        ->and(sdkConfig()->environment)->toBe('staging')
        ->and(config('settings.settings'))->toContain(BliptraceSettings::class);
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

it('passes sample_rate through to the client', function (): void {
    expect(sdkConfig()->sampleRate)->toBe(1.0);
    rebootSdk(['bliptrace.sample_rate' => '0.25']);
    expect(sdkConfig()->sampleRate)->toBe(0.25);
});

it('moves values stored under the legacy g8scope group to bliptrace on migrate', function (): void {
    createSettingsTable();
    storeSettings(['endpoint' => 'http://legacy-db', 'key' => 'legacy-key'], 'g8scope');
    $dir = __DIR__.'/../database/settings/';
    (include $dir.'2026_06_25_000000_create_bliptrace_settings.php')->up();
    (include $dir.'2026_11_27_000000_rename_legacy_settings_group.php')->up();

    $rows = DB::table('settings')->get()->groupBy('group');
    expect($rows->keys()->all())->toBe(['bliptrace'])
        ->and(json_decode($rows['bliptrace']->firstWhere('name', 'endpoint')->payload))->toBe('http://legacy-db')
        ->and($rows['bliptrace'])->toHaveCount(5);
});
