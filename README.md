# bliptrace/laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bliptrace/laravel.svg?style=flat-square)](https://packagist.org/packages/bliptrace/laravel)

Laravel SDK for [g8scope](https://scope.g8suite.com) — auto-captures exceptions and ships them to the
ingest API. A thin adapter over [`bliptrace/php`](../bliptrace-php).

## Install

```bash
composer require bliptrace/laravel
php artisan vendor:publish --tag="g8scope-config"
```

## Configure

```env
G8SCOPE_KEY=your-ingest-key
G8SCOPE_ENDPOINT=https://ingest.scope.g8suite.com
G8SCOPE_RELEASE=my-app@1.0.0
# G8SCOPE_ENABLED=false   # master kill switch
# G8SCOPE_SAMPLE_RATE=1.0 # share of events sent, 0.0–1.0 (client-side, per event)
```

`.env` / `config/g8scope.php` is the only source of settings unless you opt in to the DB
settings layer below.

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

## DB settings + settings page (optional, opt-in)

Both are **off by default**. Turn them on only if you want capture configured from the database
instead of `.env`.

| Env | Default | Effect |
|---|---|---|
| `G8SCOPE_DB_SETTINGS` | `false` | Use the `g8scope` settings group (`spatie/laravel-settings`). Registers the settings class + migration. Values **stored** in the group override config/env; an unmigrated group, an empty value or a missing table falls back to config/env. |
| `G8SCOPE_UI_ENABLED` | `false` | Register the settings page at `g8scope.ui.path` (`/g8scope/settings`). Needs `G8SCOPE_DB_SETTINGS=true` plus Laravel 11+, Livewire, Flux and `spatie/laravel-settings`. |
| `G8SCOPE_UI_GATE` | `manage-g8scope` | Ability checked on the route (`can:` middleware), on mount and on every save. |

The page is denied to everyone until you define the ability:

```php
// AppServiceProvider::boot()
Gate::define('manage-g8scope', fn ($user) => $user->isAdmin());
```

```bash
php artisan vendor:publish --tag="g8scope-settings-migrations"
php artisan migrate           # seeds the g8scope group from your current .env values
```

Once the group is migrated, its stored values win over `.env`, so changes made in the UI take effect
live. Inspect the UI prerequisites with `G8Scope\Laravel\Support\Requirements::report()`.

See [CHANGELOG](CHANGELOG.md) for release notes.

---

_Developers Hub Sdn Bhd · bliptrace/laravel_
