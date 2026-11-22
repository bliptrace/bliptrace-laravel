# bliptrace/laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bliptrace/laravel.svg?style=flat-square)](https://packagist.org/packages/bliptrace/laravel)

Laravel SDK for [g8scope](https://scope.g8suite.com) — auto-captures exceptions and ships them to the
ingest API. A thin adapter over [`bliptrace/php`](../bliptrace-php).

## Install

```bash
composer require bliptrace/laravel
php artisan vendor:publish --tag="bliptrace-config"
```

## Configure

```env
BLIPTRACE_KEY=your-ingest-key
BLIPTRACE_ENDPOINT=https://ingest.scope.g8suite.com
BLIPTRACE_RELEASE=my-app@1.0.0
# BLIPTRACE_ENABLED=false   # master kill switch
# BLIPTRACE_SAMPLE_RATE=1.0 # share of events sent, 0.0–1.0 (client-side, per event)
```

Also read: `BLIPTRACE_ENVIRONMENT` (defaults to `APP_ENV`), `BLIPTRACE_TIMEOUT`,
`BLIPTRACE_MAX_RETRIES`, `BLIPTRACE_RETRY_BASE_MS`, `BLIPTRACE_RETRY_CAP_MS`, `BLIPTRACE_MAX_FRAMES`,
`BLIPTRACE_MAX_MESSAGE`. Values land in `config('bliptrace')`.

`.env` / `config/bliptrace.php` is the only source of settings unless you opt in to the DB
settings layer below.

That's it — unhandled exceptions are captured automatically (hooked into the framework's exception
handler) and shipped **after the response** so requests are never blocked. Noise like
`ValidationException` / `NotFoundHttpException` is ignored by default (configurable).

## Manual capture

```php
use G8Scope\Laravel\Facades\Bliptrace;

try { /* ... */ } catch (\Throwable $e) {
    Bliptrace::capture($e);
}
```

PII is scrubbed client-side (configurable `scrub_keys`); request method/url/headers and the user id are
attached automatically.

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
live. Inspect the UI prerequisites with `G8Scope\Laravel\Support\Requirements::report()`.

## Upgrading from the `g8scope` names (deprecated, removed in 2.0)

1.2 renamed the user-facing names. The old ones keep working until 2.0:

| Old | New | Fallback in 1.x |
|---|---|---|
| `G8SCOPE_*` env (every variable above) | `BLIPTRACE_*` | Old name read when the new one is unset |
| `config/g8scope.php`, `config('g8scope.*')` | `config/bliptrace.php`, `config('bliptrace.*')` | A published `config/g8scope.php` is still merged when there is no `config/bliptrace.php`; `config('g8scope')` mirrors `config('bliptrace')` |
| `--tag=g8scope-config` / `g8scope-settings-migrations` | `bliptrace-config` / `bliptrace-settings-migrations` | Old tags publish the same files |
| `G8Scope` facade alias | `Bliptrace` | Old alias still registered |

Unchanged: the PHP namespace (`G8Scope\Laravel`), the DB settings group (`g8scope`), the settings
route (`/g8scope/settings`, name `g8scope.settings`), the default gate (`manage-g8scope`) and the
envelope's `sdk.name`. To migrate, rename your env vars, then
`mv config/g8scope.php config/bliptrace.php` (or re-publish with `--tag=bliptrace-config`).

See [CHANGELOG](CHANGELOG.md) for release notes.

---

_Developers Hub Sdn Bhd · bliptrace/laravel_
