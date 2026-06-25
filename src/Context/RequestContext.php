<?php

declare(strict_types=1);

namespace G8Scope\Laravel\Context;

use Illuminate\Contracts\Foundation\Application;

/** Builds the envelope context from the current Laravel request (if any). */
final class RequestContext
{
    /** @return array<string,mixed> */
    public static function capture(Application $app): array
    {
        $context = ['custom' => []];

        if (! $app->bound('request')) {
            return $context;
        }

        $request = $app->make('request');
        if (! method_exists($request, 'method') || $request->server('REQUEST_METHOD') === null) {
            return $context;
        }

        $context['request'] = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => collect($request->headers->all())
                ->reject(fn ($v, $k) => in_array(strtolower((string) $k), ['authorization', 'cookie'], true))
                ->map(fn ($v) => is_array($v) ? implode(',', $v) : $v)
                ->all(),
        ];

        if ($user = $request->user()) {
            $context['user'] = ['id' => (string) $user->getAuthIdentifier()];
        }

        return $context;
    }
}
