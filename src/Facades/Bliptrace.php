<?php

declare(strict_types=1);

namespace G8Scope\Laravel\Facades;

use G8Scope\Laravel\Reporter;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void capture(\Throwable $e)
 * @method static void addBreadcrumb(string $type, ?string $message = null, array $data = [], ?string $category = null, string $level = 'info')
 * @method static void setTag(string $key, string|int|float|bool $value)
 * @method static void setTags(array $tags)
 * @method static void setTrace(?string $traceId, ?string $spanId = null)
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
