<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('g8scope.enabled', (bool) env('G8SCOPE_ENABLED', true));
        $this->migrator->add('g8scope.key', env('G8SCOPE_KEY'));
        $this->migrator->add('g8scope.endpoint', env('G8SCOPE_ENDPOINT', 'https://ingest.scope.g8suite.com'));
        $this->migrator->add('g8scope.environment', env('G8SCOPE_ENVIRONMENT', env('APP_ENV', 'production')));
        $this->migrator->add('g8scope.release', env('G8SCOPE_RELEASE'));
    }
};
