<?php

declare(strict_types=1);

namespace Bliptrace\Laravel\Settings;

use Spatie\LaravelSettings\Settings;

/** DB-backed SDK settings (spatie/laravel-settings), editable via the UI page. */
class BliptraceSettings extends Settings
{
    public bool $enabled = true;

    public ?string $key = null;

    public string $endpoint = 'https://ingest.bliptrace.com/v1/ingest';

    public string $environment = 'production';

    public ?string $release = null;

    public static function group(): string
    {
        return 'bliptrace';
    }
}
