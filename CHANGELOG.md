# Changelog

All notable changes to `bliptrace/laravel` (formerly `g8scope/laravel`) are documented here. The
format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

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

[Unreleased]: https://github.com/bliptrace/bliptrace-laravel/compare/1.1.0...HEAD
[1.1.0]: https://github.com/bliptrace/bliptrace-laravel/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/bliptrace/bliptrace-laravel/releases/tag/1.0.0
