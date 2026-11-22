<?php

declare(strict_types=1);

namespace G8Scope\Laravel\Facades;

use G8Scope\Laravel\Reporter;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void capture(\Throwable $e)
 *
 * @see Reporter
 * @deprecated since 1.2, removed in 2.0 — use the Bliptrace facade.
 */
class G8Scope extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Reporter::class;
    }
}
