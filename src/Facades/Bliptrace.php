<?php

declare(strict_types=1);

namespace G8Scope\Laravel\Facades;

use G8Scope\Laravel\Reporter;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void capture(\Throwable $e)
 *
 * @see Reporter
 */
class Bliptrace extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Reporter::class;
    }
}
