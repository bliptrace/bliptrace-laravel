<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('bliptrace.enabled', (bool) env('BLIPTRACE_ENABLED', env('G8SCOPE_ENABLED', true)));
        $this->migrator->add('bliptrace.key', env('BLIPTRACE_KEY', env('G8SCOPE_KEY')));
        $this->migrator->add('bliptrace.endpoint', env('BLIPTRACE_ENDPOINT', env('G8SCOPE_ENDPOINT', 'https://ingest.bliptrace.com/v1/ingest')));
        $this->migrator->add('bliptrace.environment', env('BLIPTRACE_ENVIRONMENT', env('G8SCOPE_ENVIRONMENT', env('APP_ENV', 'production'))));
        $this->migrator->add('bliptrace.release', env('BLIPTRACE_RELEASE', env('G8SCOPE_RELEASE')));
    }
};
