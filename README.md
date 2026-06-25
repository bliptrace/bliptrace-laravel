# g8scope/laravel

Laravel SDK for [g8scope](https://scope.g8suite.com) — auto-captures exceptions and ships them to the
ingest API. A thin adapter over [`g8scope/php`](../g8scope-php).

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
