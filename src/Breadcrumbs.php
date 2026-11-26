<?php

declare(strict_types=1);

namespace Bliptrace\Laravel;

use Bliptrace\Php\Client;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Routing\Events\RouteMatched;
use Throwable;

/** Turns framework events into breadcrumbs on the SDK client (bliptrace.breadcrumbs.*). */
final class Breadcrumbs
{
    /** PSR-3 → envelope level. */
    private const LOG_LEVELS = [
        'emergency' => 'fatal', 'alert' => 'fatal', 'critical' => 'fatal',
        'error' => 'error', 'warning' => 'warning',
        'notice' => 'info', 'info' => 'info', 'debug' => 'debug',
    ];

    /** Set while a breadcrumb is being recorded: events fired meanwhile (e.g. the settings query run while the client is first built) are skipped. */
    private bool $recording = false;

    public function __construct(private readonly Application $app) {}

    /** @param array<string,mixed> $toggles */
    public function register(array $toggles): void
    {
        $events = $this->app['events'];

        if ($toggles['requests'] ?? true) {
            $events->listen(RouteMatched::class, function (RouteMatched $e): void {
                $this->add('http', $e->request->method().' '.$e->request->getPathInfo(), array_filter([
                    'method' => $e->request->method(),
                    'url' => $e->request->url(), // no query string: it is often PII
                    'route' => $e->route->getName() ?: $e->route->getActionName(),
                ]), 'request');
            });
        }

        if ($toggles['queries'] ?? true) {
            // SQL text only: bindings (the actual values) are never recorded.
            $events->listen(QueryExecuted::class, function (QueryExecuted $e): void {
                $this->add('query', $e->sql, ['connection' => $e->connectionName, 'duration_ms' => $e->time], 'db.query');
            });
        }

        if ($toggles['logs'] ?? true) {
            $events->listen(MessageLogged::class, function (MessageLogged $e): void {
                $data = array_filter($e->context, fn ($v, $k) => $k !== 'exception' && (is_scalar($v) || is_array($v)), ARRAY_FILTER_USE_BOTH);
                $this->add('log', (string) $e->message, $data, 'log', self::LOG_LEVELS[$e->level] ?? 'info');
            });
        }

        if ($toggles['jobs'] ?? true) {
            $events->listen(JobProcessing::class, function (JobProcessing $e): void {
                $this->add('queue', $e->job->resolveName(), ['connection' => $e->connectionName, 'queue' => $e->job->getQueue()], 'job');
            });
        }

        if ($toggles['cache'] ?? false) {
            $events->listen(CacheMissed::class, function (CacheMissed $e): void {
                $this->add('cache', 'miss: '.$e->key, ['key' => $e->key], 'cache.miss', 'debug');
            });
        }
    }

    /** @param array<string,mixed> $data */
    private function add(string $type, string $message, array $data, string $category, string $level = 'info'): void
    {
        if ($this->recording) {
            return;
        }
        $this->recording = true;
        try {
            $this->app->make(Client::class)->addBreadcrumb($type, $message, $data, $category, $level);
        } catch (Throwable) {
            // Never break the app.
        } finally {
            $this->recording = false;
        }
    }
}
