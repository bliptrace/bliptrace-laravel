<?php

declare(strict_types=1);

use G8Scope\Laravel\Livewire\SettingsPage;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

$on = ['bliptrace.ui.enabled' => true, 'bliptrace.settings.enabled' => true];

it('does not register the page by default, even with every UI dependency present', function (): void {
    rebootSdk();
    expect(Route::has('g8scope.settings'))->toBeFalse();
});

it('does not register the page without the DB settings layer', function (): void {
    rebootSdk(['bliptrace.ui.enabled' => true]);
    expect(Route::has('g8scope.settings'))->toBeFalse();
});

it('registers the page behind the configured gate when opted in', function () use ($on): void {
    rebootSdk([...$on, 'bliptrace.ui.gate' => 'edit-error-tracking']);

    expect(Route::has('g8scope.settings'))->toBeTrue()
        ->and(Route::getRoutes()->getByName('g8scope.settings')->gatherMiddleware())
        ->toContain('auth', 'can:edit-error-tracking');
});

it('denies a signed-in user when the gate is not defined', function () use ($on): void {
    rebootSdk($on);
    $this->actingAs(new GenericUser(['id' => 1]))->get('/g8scope/settings')->assertForbidden();
});

it('denies a signed-in user the gate rejects', function () use ($on): void {
    rebootSdk($on);
    Gate::define('manage-g8scope', fn () => false);
    $this->actingAs(new GenericUser(['id' => 1]))->get('/g8scope/settings')->assertForbidden();
});

it('lets the gate through to the page', function () use ($on): void {
    createSettingsTable();
    rebootSdk($on);
    Gate::define('manage-g8scope', fn () => true);

    view()->addNamespace('layouts', __DIR__.'/fixtures/layouts');

    $this->actingAs(new GenericUser(['id' => 1]))->get('/g8scope/settings')
        ->assertOk()
        ->assertSee('settings', false);
});

it('re-checks the gate on save (Livewire updates bypass the route middleware)', function () use ($on): void {
    createSettingsTable();
    rebootSdk($on);
    $allowed = true;
    Gate::define('manage-g8scope', function () use (&$allowed) {
        return $allowed;
    });
    view()->addNamespace('layouts', __DIR__.'/fixtures/layouts');
    $this->actingAs(new GenericUser(['id' => 1]));

    $page = Livewire\Livewire::test(SettingsPage::class);
    $allowed = false;
    $page->set('endpoint', 'http://evil')->call('save')->assertForbidden();
});
