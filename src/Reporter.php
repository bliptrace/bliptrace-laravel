<?php

declare(strict_types=1);

namespace Bliptrace\Laravel;

use Bliptrace\Laravel\Context\RequestContext;
use Bliptrace\Php\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Throwable;

/** Laravel reporter: enrich with request context, ship after the response. */
final class Reporter
{
    public function __construct(
        private readonly Application $app,
        private readonly Client $client,
    ) {}

    public function capture(Throwable $e): void
    {
        try {
            // Incoming W3C trace context, when the request carries one.
            if ($this->app->bound('request') && ($request = $this->app->make('request')) instanceof Request) {
                $this->client->setTraceparent($request->header('traceparent'));
            }

            $envelope = $this->client->buildEnvelope($e, RequestContext::capture($this->app));
            if ($envelope === null) {
                return;
            }

            // Ship after the response so the request is never blocked.
            $this->app->terminating(fn () => $this->client->sendEnvelope($envelope));
        } catch (Throwable) {
            // Never break the app.
        }
    }

    /** @param array<string,mixed> $data */
    public function addBreadcrumb(string $type, ?string $message = null, array $data = [], ?string $category = null, string $level = 'info'): void
    {
        $this->client->addBreadcrumb($type, $message, $data, $category, $level);
    }

    public function setTag(string $key, string|int|float|bool $value): void
    {
        $this->client->setTag($key, $value);
    }

    /** @param array<string,string|int|float|bool> $tags */
    public function setTags(array $tags): void
    {
        $this->client->setTags($tags);
    }

    public function setTrace(?string $traceId, ?string $spanId = null): void
    {
        $this->client->setTrace($traceId, $spanId);
    }
}
