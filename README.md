# g8scope/laravel

Laravel SDK for [g8scope](https://scope.g8suite.com) — auto-captures exceptions and ships them to the
ingest API. A thin adapter over [`g8scope/php`](../bliptrace-php).

## Install

```bash
composer require g8scope/laravel
php artisan vendor:publish --tag="g8scope-config"
```

## Configure

```env
G8SCOPE_KEY=your-ingest-key
G8SCOPE_ENDPOINT=https://ingest.scope.g8suite.com
G8SCOPE_RELEASE=my-app@1.0.0
# G8SCOPE_ENABLED=false   # master kill switch
```

That's it — unhandled exceptions are captured automatically (hooked into the framework's exception
handler) and shipped **after the response** so requests are never blocked. Noise like
`ValidationException` / `NotFoundHttpException` is ignored by default (configurable).

## Manual capture

```php
use G8Scope\Laravel\Facades\G8Scope;

try { /* ... */ } catch (\Throwable $e) {
    G8Scope::capture($e);
}
```

PII is scrubbed client-side (configurable `scrub_keys`); request method/url/headers and the user id are
attached automatically.

---

_Developers Hub Sdn Bhd · g8scope/laravel_

## UI settings page (optional, version-gated)

When the host app meets every requirement — **Laravel 11+, Livewire, Flux, and
`spatie/laravel-settings`** — the SDK publishes a settings page at `/g8scope/settings`
(configurable via `g8scope.ui.path`) so you can configure capture from the UI instead of
`.env`. It follows the same conventions as a Kickoff settings page.

```bash
php artisan vendor:publish --tag="g8scope-settings-migrations"
php artisan migrate           # seeds the g8scope settings group (from your .env defaults)
```

Config precedence is **DB settings → config → env defaults**, so UI changes take effect live.

If the requirements are **not** met, nothing UI-related is published — the SDK runs **headless**
(captures via config/env) and exposes no route, page, or migration. Inspect the gate with
`G8Scope\Laravel\Support\Requirements::report()`.
