<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/*
 * Legacy shim: 1.x stored the settings group as `g8scope`. Move any stored
 * values to the `bliptrace` group, replacing the env-seeded defaults that the
 * create migration just added. A no-op on fresh installs. Removed in 3.0.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach (['enabled', 'key', 'endpoint', 'environment', 'release'] as $name) {
            if (! $this->migrator->exists("g8scope.{$name}")) {
                continue;
            }
            $this->migrator->deleteIfExists("bliptrace.{$name}");
            $this->migrator->rename("g8scope.{$name}", "bliptrace.{$name}");
        }
    }
};
