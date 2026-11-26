<?php

declare(strict_types=1);

use Bliptrace\Laravel\Support\Requirements;

it('reports each requirement', function (): void {
    $report = Requirements::report();
    expect($report)->toHaveKeys(['laravel_11_plus', 'livewire', 'flux', 'spatie_settings'])
        ->and($report['laravel_11_plus'])->toBeTrue(); // Testbench is Laravel 11+
});

it('reports the UI prerequisites as met when Livewire, Flux + spatie-settings are installed', function (): void {
    // Dev deps install all three; opting in is still separate (see SettingsUiTest).
    expect(Requirements::uiAvailable())->toBeTrue();
});
