<?php

return [
    // Master kill switch — set false to disable all capture.
    'enabled' => env('G8SCOPE_ENABLED', true),

    // Project ingest key (from the g8scope dashboard) + the ingest endpoint.
    'key' => env('G8SCOPE_KEY'),
    'endpoint' => env('G8SCOPE_ENDPOINT', 'https://ingest.scope.g8suite.com'),

    'environment' => env('G8SCOPE_ENVIRONMENT', env('APP_ENV', 'production')),
    'release' => env('G8SCOPE_RELEASE'),

    // HTTP timeout (seconds) for the async send — kept short so a slow ingest
    // never affects the monitored app.
    'timeout' => env('G8SCOPE_TIMEOUT', 3),

    // Cap the number of stack frames + the message length sent.
    'max_frames' => env('G8SCOPE_MAX_FRAMES', 30),
    'max_message' => env('G8SCOPE_MAX_MESSAGE', 2000),

    // Exceptions never reported (noise). Add your own.
    'ignore_exceptions' => [
        Illuminate\Auth\AuthenticationException::class,
        Illuminate\Auth\Access\AuthorizationException::class,
        Illuminate\Validation\ValidationException::class,
        Illuminate\Session\TokenMismatchException::class,
        Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
    ],

    // Request context keys redacted before sending (client-side scrub).
    'scrub_keys' => [
        'password', 'password_confirmation', 'token', 'secret', 'authorization',
        'api_key', 'apikey', 'access_token', 'refresh_token', 'cookie', 'set-cookie',
    ],
];
