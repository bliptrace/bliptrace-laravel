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

    // Client-side sampling: share of events sent, 0.0–1.0 (per event, random).
    // The ingest API applies its own server-side sampling on top.
    'sample_rate' => env('G8SCOPE_SAMPLE_RATE', 1.0),

    // HTTP timeout (seconds) for the async send — kept short so a slow ingest
    // never affects the monitored app.
    'timeout' => env('G8SCOPE_TIMEOUT', 3),

    // Transport retry: when the ingest API is briefly down or shedding (429), the
    // send is retried with exponential backoff + jitter. Safe because the same
    // event_id is replayed and the API dedupes on it (no duplicate occurrences).
    // Kept to a small budget so it only runs in the deferred (after-response) send.
    'max_retries' => env('G8SCOPE_MAX_RETRIES', 2),
    'retry_base_ms' => env('G8SCOPE_RETRY_BASE_MS', 100),
    'retry_cap_ms' => env('G8SCOPE_RETRY_CAP_MS', 2000),

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

    // Optional DB-backed settings (spatie/laravel-settings). Off by default:
    // config/env is the only source. When on, values stored in the `g8scope`
    // settings group override config/env; an unmigrated group changes nothing.
    'settings' => [
        'enabled' => env('G8SCOPE_DB_SETTINGS', false),
    ],

    // Optional settings page. Off by default. Needs settings.enabled plus
    // Livewire, Flux and spatie/laravel-settings. Every request is checked
    // against `gate` — define it (Gate::define('manage-g8scope', ...)) or the
    // page answers 403 to everyone.
    'ui' => [
        'enabled' => env('G8SCOPE_UI_ENABLED', false),
        'path' => env('G8SCOPE_UI_PATH', 'g8scope/settings'),
        'middleware' => ['web', 'auth'],
        'gate' => env('G8SCOPE_UI_GATE', 'manage-g8scope'),
    ],

    // Request context keys redacted before sending (client-side scrub).
    'scrub_keys' => [
        'password', 'password_confirmation', 'token', 'secret', 'authorization',
        'api_key', 'apikey', 'access_token', 'refresh_token', 'cookie', 'set-cookie',
    ],
];
