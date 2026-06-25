<?php

declare(strict_types=1);

namespace G8Scope\Laravel;

use G8Scope\Laravel\Context\RequestContext;
use G8Scope\Php\Client;
use Illuminate\Contracts\Foundation\Application;
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
            $envelope = $this->client->buildEnvelope($e, RequestContext::capture($this->app));
            if ($envelope === null) {
                return;
            }

            // Ship after the response so the request is never blocked.
            $this->app->terminating(fn () => $this->client->sendEnvelope($envelope));
        } catch (\Throwable) {
            // Never break the app.
        }
    }
}
