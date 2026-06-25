<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        AuthenticationException::class,
        AuthorizationException::class,
        ValidationException::class,
        TokenMismatchException::class,
        NotFoundHttpException::class,
        MethodNotAllowedHttpException::class,
    ],

    // Optional UI settings page (published only when Requirements::uiAvailable()).
    'ui' => [
        'path' => env('G8SCOPE_UI_PATH', 'g8scope/settings'),
        'middleware' => ['web', 'auth'],
    ],

    // Request context keys redacted before sending (client-side scrub).
    'scrub_keys' => [
        'password', 'password_confirmation', 'token', 'secret', 'authorization',
        'api_key', 'apikey', 'access_token', 'refresh_token', 'cookie', 'set-cookie',
    ],
];
