<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('g8scope.enabled', (bool) env('BLIPTRACE_ENABLED', env('G8SCOPE_ENABLED', true)));
        $this->migrator->add('g8scope.key', env('BLIPTRACE_KEY', env('G8SCOPE_KEY')));
        $this->migrator->add('g8scope.endpoint', env('BLIPTRACE_ENDPOINT', env('G8SCOPE_ENDPOINT', 'https://ingest.scope.g8suite.com')));
        $this->migrator->add('g8scope.environment', env('BLIPTRACE_ENVIRONMENT', env('G8SCOPE_ENVIRONMENT', env('APP_ENV', 'production'))));
        $this->migrator->add('g8scope.release', env('BLIPTRACE_RELEASE', env('G8SCOPE_RELEASE')));
    }
};
