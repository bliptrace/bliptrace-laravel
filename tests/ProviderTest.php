<?php

declare(strict_types=1);

use Bliptrace\Php\Client;
use Illuminate\Support\Facades\Route;

it('does not publish the settings route unless opted in', function (): void {
    expect(Route::has('bliptrace.settings'))->toBeFalse();
});

it('still resolves the capture client headless', function (): void {
    expect(app(Client::class))->toBeInstanceOf(Client::class);
});
