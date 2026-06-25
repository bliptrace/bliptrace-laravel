<?php

declare(strict_types=1);

namespace G8Scope\Laravel\Tests;

use G8Scope\Laravel\G8ScopeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [G8ScopeServiceProvider::class];
    }
}
