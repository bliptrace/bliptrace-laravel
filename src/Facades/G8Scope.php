<?php

declare(strict_types=1);

namespace G8Scope\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void capture(\Throwable $e)
 *
 * @see \G8Scope\Laravel\Reporter
 */
class G8Scope extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \G8Scope\Laravel\Reporter::class;
    }
}
