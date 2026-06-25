<?php

declare(strict_types=1);

use G8Scope\Laravel\Support\Requirements;

it('reports each requirement', function (): void {
    $report = Requirements::report();
    expect($report)->toHaveKeys(['laravel_11_plus', 'livewire', 'flux', 'spatie_settings'])
        ->and($report['laravel_11_plus'])->toBeTrue(); // Testbench is Laravel 11+
});

it('keeps the UI unavailable unless Flux + spatie-settings are present', function (): void {
    // Bare Testbench has neither Flux nor spatie/laravel-settings installed.
    expect(Requirements::uiAvailable())->toBeFalse();
});
