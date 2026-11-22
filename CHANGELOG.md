# Changelog

All notable changes to `bliptrace/laravel` (formerly `g8scope/laravel`) are documented here. The
format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.2.0] - 2026-11-23

### Added
- `BLIPTRACE_*` environment variables for every setting: `BLIPTRACE_KEY`, `BLIPTRACE_ENDPOINT`,
  `BLIPTRACE_ENABLED`, `BLIPTRACE_ENVIRONMENT`, `BLIPTRACE_RELEASE`, `BLIPTRACE_SAMPLE_RATE`,
  `BLIPTRACE_TIMEOUT`, `BLIPTRACE_MAX_RETRIES`, `BLIPTRACE_RETRY_BASE_MS`, `BLIPTRACE_RETRY_CAP_MS`,
  `BLIPTRACE_MAX_FRAMES`, `BLIPTRACE_MAX_MESSAGE`, `BLIPTRACE_DB_SETTINGS`, `BLIPTRACE_UI_ENABLED`,
  `BLIPTRACE_UI_PATH`, `BLIPTRACE_UI_GATE`.
- `Bliptrace` facade (`G8Scope\Laravel\Facades\Bliptrace`) and alias.
- `bliptrace-config` and `bliptrace-settings-migrations` publish tags.

### Changed
- Config file and key renamed `config/g8scope.php` / `config('g8scope')` →
  `config/bliptrace.php` / `config('bliptrace')`.
- Requires `bliptrace/php: ^1.2`.

### Deprecated (removed in 2.0)
- `G8SCOPE_*` env names: still read when the matching `BLIPTRACE_*` name is unset.
- A published `config/g8scope.php`: still merged over the defaults when `config/bliptrace.php` is
  not published; `config('g8scope')` mirrors `config('bliptrace')`.
- `G8Scope` facade alias and the `g8scope-config` / `g8scope-settings-migrations` tags.

Unchanged: the `G8Scope\Laravel` namespace, the `g8scope` settings group, the `/g8scope/settings`
route and `manage-g8scope` gate defaults, and the envelope `sdk.name`.

## [1.1.0] - 2026-11-23

### Added
- `sample_rate` config / `G8SCOPE_SAMPLE_RATE` (0.0–1.0, default 1.0): client-side, per-event
  random sampling via `bliptrace/php` 1.1.
- `g8scope.settings.enabled` / `G8SCOPE_DB_SETTINGS` (default `false`): opt-in switch for the
  DB-backed settings layer.
- `g8scope.ui.enabled` / `G8SCOPE_UI_ENABLED` (default `false`) and `g8scope.ui.gate` /
  `G8SCOPE_UI_GATE` (default `manage-g8scope`) for the settings page.

### Changed
- **Package renamed `g8scope/laravel` → `bliptrace/laravel`**, and it now requires
  `bliptrace/php: ^1.1` (was `g8scope/php: *`). Run
  `composer remove g8scope/laravel && composer require bliptrace/laravel:^1.1`. The PHP namespace
  (`G8Scope\Laravel`), the `g8scope` config key, `G8SCOPE_*` env names and the `G8Scope` facade
  are unchanged.

### Changed / security
- **The DB settings layer is now opt-in.** 1.0 read `G8ScopeSettings` whenever
  `spatie/laravel-settings` was installed. spatie fills an unmigrated group with the class
  defaults, so `G8SCOPE_ENDPOINT` / `G8SCOPE_ENABLED` were silently replaced by the built-in
  endpoint and `enabled = true`. Now config/env is the only source unless
  `G8SCOPE_DB_SETTINGS=true`, and even then only values actually stored in the group override it.
- **The `/g8scope/settings` page is opt-in and gated.** 1.0 registered it automatically with
  `web,auth` only, so any signed-in user could change or switch off error tracking. It now needs
  `G8SCOPE_UI_ENABLED=true` and `G8SCOPE_DB_SETTINGS=true`, and every request, mount and save is
  checked against the `g8scope.ui.gate` ability. An undefined ability denies everyone. If you
  relied on the 1.0 page, set both flags and define the ability.

## [1.0.0] - 2026-07-15

### Added
- Initial release as `g8scope/laravel`: exception-handler hook, deferred send, request context,
  facade, optional Livewire/Flux settings page.

[Unreleased]: https://github.com/bliptrace/bliptrace-laravel/compare/1.2.0...HEAD
[1.2.0]: https://github.com/bliptrace/bliptrace-laravel/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/bliptrace/bliptrace-laravel/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/bliptrace/bliptrace-laravel/releases/tag/1.0.0
