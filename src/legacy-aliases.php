<?php

declare(strict_types=1);

/*
 * Legacy shim: 1.x shipped under the G8Scope\Laravel namespace. Any
 * G8Scope\Laravel\* class is aliased to its Bliptrace\Laravel\* replacement on
 * first use (G8Scope in the class name becomes Bliptrace, e.g.
 * G8ScopeServiceProvider -> BliptraceServiceProvider, Facades\G8Scope ->
 * Facades\Bliptrace), with one E_USER_DEPRECATED per process. Removed in 3.0.
 */
spl_autoload_register(static function (string $class): void {
    static $warned = false;

    if (! str_starts_with($class, 'G8Scope\\Laravel\\')) {
        return;
    }

    $target = str_replace('G8Scope', 'Bliptrace', $class);
    if (! class_exists($target) && ! interface_exists($target) && ! trait_exists($target) && ! enum_exists($target)) {
        return;
    }

    if (! $warned) {
        $warned = true;
        @trigger_error('The G8Scope\\Laravel namespace is deprecated since bliptrace/laravel 2.0 and will be removed in 3.0; use Bliptrace\\Laravel.', E_USER_DEPRECATED);
    }

    class_alias($target, $class);
});
