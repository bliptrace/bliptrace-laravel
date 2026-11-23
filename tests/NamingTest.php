<?php

declare(strict_types=1);

use G8Scope\Laravel\Facades\Bliptrace;
use G8Scope\Laravel\Facades\G8Scope;
use G8Scope\Laravel\Reporter;

/** Evaluate the package config file against the given env (null = unset). */
function configWithEnv(array $env): array
{
    foreach ($env as $var => $value) {
        if ($value === null) {
            unset($_ENV[$var], $_SERVER[$var]);
            putenv($var);
        } else {
            $_ENV[$var] = $_SERVER[$var] = $value;
        }
    }

    try {
        return require __DIR__.'/../config/bliptrace.php';
    } finally {
        foreach (array_keys($env) as $var) {
            unset($_ENV[$var], $_SERVER[$var]);
            putenv($var);
        }
    }
}

it('reads BLIPTRACE_* over G8SCOPE_*', function (): void {
    $c = configWithEnv([
        'BLIPTRACE_KEY' => 'new-key', 'G8SCOPE_KEY' => 'old-key',
        'BLIPTRACE_UI_GATE' => 'new-gate', 'G8SCOPE_UI_GATE' => 'old-gate',
    ]);

    expect($c['key'])->toBe('new-key')->and($c['ui']['gate'])->toBe('new-gate');
});

it('still reads G8SCOPE_* when the BLIPTRACE_* name is unset', function (): void {
    $c = configWithEnv([
        'BLIPTRACE_ENDPOINT' => null, 'G8SCOPE_ENDPOINT' => 'http://old',
        'BLIPTRACE_DB_SETTINGS' => null, 'G8SCOPE_DB_SETTINGS' => 'true',
    ]);

    expect($c['endpoint'])->toBe('http://old')->and($c['settings']['enabled'])->toBeTrue();
});

it('uses the defaults when neither name is set', function (): void {
    $c = configWithEnv([
        'BLIPTRACE_ENDPOINT' => null, 'G8SCOPE_ENDPOINT' => null,
        'BLIPTRACE_TIMEOUT' => null, 'G8SCOPE_TIMEOUT' => null,
        'BLIPTRACE_UI_GATE' => null, 'G8SCOPE_UI_GATE' => null,
    ]);

    expect($c['endpoint'])->toBe('https://ingest.bliptrace.com/v1/ingest')
        ->and($c['timeout'])->toBe(3)
        ->and($c['ui']['gate'])->toBe('manage-g8scope');
});

it('reads a legacy published config/g8scope.php when there is no config/bliptrace.php', function (): void {
    config()->set('bliptrace', null); // as if config/bliptrace.php was never published
    rebootSdk(['g8scope' => ['key' => 'legacy-key', 'endpoint' => 'http://legacy']]);

    expect(sdkConfig()->key)->toBe('legacy-key')
        ->and(sdkConfig()->endpoint)->toBe('http://legacy')
        ->and(config('bliptrace.timeout'))->toBe(3)          // package defaults still merged
        ->and(config('g8scope.key'))->toBe('legacy-key');    // old config() reads keep working
});

it('prefers a published config/bliptrace.php over a legacy config/g8scope.php', function (): void {
    config()->set('bliptrace', ['key' => 'new-key']);
    rebootSdk(['g8scope' => ['key' => 'legacy-key']]);

    expect(sdkConfig()->key)->toBe('new-key');
});

it('resolves both the Bliptrace facade and the deprecated G8Scope facade', function (): void {
    expect(Bliptrace::getFacadeRoot())->toBeInstanceOf(Reporter::class)
        ->and(G8Scope::getFacadeRoot())->toBe(Bliptrace::getFacadeRoot());
});
