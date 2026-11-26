# bliptrace/laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bliptrace/laravel.svg?style=flat-square)](https://packagist.org/packages/bliptrace/laravel)

Laravel SDK for [Bliptrace](https://bliptrace.com) — auto-captures exceptions and ships them to the
ingest API. A thin adapter over [`bliptrace/php`](../bliptrace-php).

## Install

```bash
composer require bliptrace/laravel
php artisan vendor:publish --tag="bliptrace-config"
```

## Configure

```env
BLIPTRACE_KEY=your-ingest-key
# BLIPTRACE_ENDPOINT=https://ingest.bliptrace.com/v1/ingest  # the default
BLIPTRACE_RELEASE=my-app@1.0.0
# BLIPTRACE_ENABLED=false   # master kill switch
# BLIPTRACE_SAMPLE_RATE=1.0 # share of events sent, 0.0–1.0 (client-side, per event)
```

Also read: `BLIPTRACE_ENVIRONMENT` (defaults to `APP_ENV`), `BLIPTRACE_TIMEOUT`,
`BLIPTRACE_MAX_RETRIES`, `BLIPTRACE_RETRY_BASE_MS`, `BLIPTRACE_RETRY_CAP_MS`, `BLIPTRACE_MAX_FRAMES`,
`BLIPTRACE_MAX_MESSAGE`, `BLIPTRACE_MAX_BREADCRUMBS`. Values land in `config('bliptrace')`.

`.env` / `config/bliptrace.php` is the only source of settings unless you opt in to the DB
settings layer below.

That's it — unhandled exceptions are captured automatically (hooked into the framework's exception
handler) and shipped **after the response** so requests are never blocked. Noise like
`ValidationException` / `NotFoundHttpException` is ignored by default (configurable).

## Manual capture

```php
use Bliptrace\Laravel\Facades\Bliptrace;

try { /* ... */ } catch (\Throwable $e) {
    Bliptrace::capture($e);
}
```

PII is scrubbed client-side (configurable `scrub_keys`); request method/url/headers and the user id are
attached automatically.

## Breadcrumbs, tags and trace

Each reported error carries the trail of events that led up to it. Breadcrumbs are kept in
memory (the newest `max_breadcrumbs`, default 50) and only sent with an error, so `enabled` and
`sample_rate` apply to them too.

| Source | Config key | Env | Default | Recorded |
|---|---|---|---|---|
| HTTP request start (route matched) | `breadcrumbs.requests` | `BLIPTRACE_BREADCRUMBS_REQUESTS` | `true` | method, URL without query string, route name or action |
| Executed queries | `breadcrumbs.queries` | `BLIPTRACE_BREADCRUMBS_QUERIES` | `true` | SQL text with `?` placeholders, connection, duration (ms). Bindings are never recorded |
| Log messages | `breadcrumbs.logs` | `BLIPTRACE_BREADCRUMBS_LOGS` | `true` | level, message, scalar/array context (the `exception` key is dropped) |
| Queued job processing | `breadcrumbs.jobs` | `BLIPTRACE_BREADCRUMBS_JOBS` | `true` | job class, connection, queue |
| Cache misses | `breadcrumbs.cache` | `BLIPTRACE_BREADCRUMBS_CACHE` | `false` | cache key |

`BLIPTRACE_MAX_BREADCRUMBS=0` turns breadcrumbs off. Breadcrumb data goes through `scrub_keys`
before send, like the rest of the context.

Tags set in `config/bliptrace.php` are sent with every event:

```php
'tags' => ['tenant' => 'umpsa', 'region' => 'my'],
```

An incoming W3C `traceparent` header is read into the envelope's `trace` (`trace_id`, `span_id`)
when an exception is reported during that request.

Add your own from code:

```php
use Bliptrace\Laravel\Facades\Bliptrace;

Bliptrace::addBreadcrumb('user', 'Clicked pay', ['plan' => 'pro'], category: 'ui');
Bliptrace::setTag('tenant', $tenant->slug);
Bliptrace::setTags(['region' => 'my']);
Bliptrace::setTrace($traceId, $spanId);
```

## DB settings + settings page (optional, opt-in)

Both are **off by default**. Turn them on only if you want capture configured from the database
instead of `.env`.

| Env | Default | Effect |
|---|---|---|
| `BLIPTRACE_DB_SETTINGS` | `false` | Use the `g8scope` settings group (`spatie/laravel-settings`). Registers the settings class + migration. Values **stored** in the group override config/env; an unmigrated group, an empty value or a missing table falls back to config/env. |
| `BLIPTRACE_UI_ENABLED` | `false` | Register the settings page at `bliptrace.ui.path` (`/g8scope/settings`, env `BLIPTRACE_UI_PATH`). Needs `BLIPTRACE_DB_SETTINGS=true` plus Laravel 11+, Livewire, Flux and `spatie/laravel-settings`. |
| `BLIPTRACE_UI_GATE` | `manage-g8scope` | Ability checked on the route (`can:` middleware), on mount and on every save. |

The page is denied to everyone until you define the ability:

```php
// AppServiceProvider::boot()
Gate::define('manage-g8scope', fn ($user) => $user->isAdmin());
```

```bash
php artisan vendor:publish --tag="bliptrace-settings-migrations"
php artisan migrate           # seeds the g8scope group from your current .env values
```

Once the group is migrated, its stored values win over `.env`, so changes made in the UI take effect
live. Inspect the UI prerequisites with `Bliptrace\Laravel\Support\Requirements::report()`.

## Upgrading from the `g8scope` names (deprecated, removed in 2.0)

1.2 renamed the user-facing names. The old ones keep working until 2.0:

| Old | New | Fallback in 1.x |
|---|---|---|
| `G8SCOPE_*` env (every variable above) | `BLIPTRACE_*` | Old name read when the new one is unset |
| `config/g8scope.php`, `config('g8scope.*')` | `config/bliptrace.php`, `config('bliptrace.*')` | A published `config/g8scope.php` is still merged when there is no `config/bliptrace.php`; `config('g8scope')` mirrors `config('bliptrace')` |
| `--tag=g8scope-config` / `g8scope-settings-migrations` | `bliptrace-config` / `bliptrace-settings-migrations` | Old tags publish the same files |
| `G8Scope` facade alias | `Bliptrace` | Old alias still registered |

Unchanged: the PHP namespace (`Bliptrace\Laravel`), the DB settings group (`g8scope`), the settings
route (`/g8scope/settings`, name `g8scope.settings`), the default gate (`manage-g8scope`) and the
envelope's `sdk.name`. To migrate, rename your env vars, then
`mv config/g8scope.php config/bliptrace.php` (or re-publish with `--tag=bliptrace-config`).

See [CHANGELOG](CHANGELOG.md) for release notes.

---

_Developers Hub Sdn Bhd · bliptrace/laravel_
