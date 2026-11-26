<?php

declare(strict_types=1);

use Bliptrace\Laravel\Facades\Bliptrace;
use Bliptrace\Laravel\Reporter;
use Bliptrace\Php\Client;
use Bliptrace\Php\Config;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

final class BreadcrumbTestJob implements ShouldQueue
{
    use Dispatchable;

    public function handle(): void {}
}

/** Swap in a client that ships to $t, re-registering listeners for $config. */
function fakeSdk(FakeTransport $t, array $config = []): Client
{
    // Drop the listeners registered at boot so only $config's toggles apply.
    foreach ([RouteMatched::class, QueryExecuted::class, MessageLogged::class, JobProcessing::class, CacheMissed::class] as $event) {
        app('events')->forget($event);
    }
    rebootSdk($config);
    $client = new Client(Config::fromArray(['key' => 'k'] + config('bliptrace')), $t);
    app()->instance(Client::class, $client);
    app()->forgetInstance(Reporter::class);
    Bliptrace::clearResolvedInstances();

    return $client;
}

/** Capture an exception and ship it (as at the end of a request). */
function reported(FakeTransport $t): ?array
{
    app(Reporter::class)->capture(new RuntimeException('boom'));
    app()->terminate();

    return $t->sent === [] ? null : end($t->sent)['envelope'];
}

function crumbsOfType(array $envelope, string $type): array
{
    return array_values(array_filter($envelope['breadcrumbs'] ?? [], fn ($c) => $c['type'] === $type));
}

it('records the request, its queries and logs, and reads traceparent', function (): void {
    $t = new FakeTransport;
    fakeSdk($t);
    Route::get('/orders/{id}', function () {
        DB::select('select ? as id', [42]);
        Log::warning('order slow', ['order' => 42, 'password' => 'hunter2', 'exception' => new Exception('x')]);
        throw new RuntimeException('order failed');
    })->name('orders.show');

    $this->get('/orders/7?secret=1', ['traceparent' => '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01']);
    app()->terminate();

    $env = end($t->sent)['envelope'];
    $http = crumbsOfType($env, 'http')[0];
    $query = crumbsOfType($env, 'query')[0];
    $log = crumbsOfType($env, 'log')[0];

    expect($env['exception']['message'])->toBe('order failed')
        ->and($http)->toMatchArray(['category' => 'request', 'message' => 'GET /orders/7'])
        ->and($http['data'])->toBe(['method' => 'GET', 'url' => 'http://localhost/orders/7', 'route' => 'orders.show'])
        ->and($query['message'])->toBe('select ? as id')
        ->and($query['data'])->toHaveKeys(['connection', 'duration_ms'])
        ->and(json_encode($query))->not->toContain('42')
        ->and($log)->toMatchArray(['level' => 'warning', 'message' => 'order slow'])
        ->and($log['data'])->toBe(['order' => 42, 'password' => '[REDACTED]'])
        ->and($env['trace'])->toBe(['trace_id' => '4bf92f3577b34da6a3ce929d0e0e4736', 'span_id' => '00f067aa0ba902b7']);
});

it('records queued job processing', function (): void {
    $t = new FakeTransport;
    fakeSdk($t);
    BreadcrumbTestJob::dispatch(); // sync queue in tests

    $job = crumbsOfType(reported($t), 'queue')[0];
    expect($job['message'])->toBe(BreadcrumbTestJob::class)
        ->and($job['category'])->toBe('job');
});

it('records cache misses only when switched on', function (): void {
    $t = new FakeTransport;
    fakeSdk($t);
    Cache::get('missing-key');
    expect(crumbsOfType(reported($t), 'cache'))->toBe([]);

    fakeSdk($t, ['bliptrace.breadcrumbs.cache' => true]);
    Cache::get('missing-key');
    expect(crumbsOfType(reported($t), 'cache')[0]['message'])->toBe('miss: missing-key');
});

it('honours the per-source toggles', function (): void {
    $t = new FakeTransport;
    fakeSdk($t, ['bliptrace.breadcrumbs.queries' => false, 'bliptrace.breadcrumbs.logs' => false]);
    DB::select('select 1');
    Log::info('hello');

    $env = reported($t);
    expect(crumbsOfType($env, 'query'))->toBe([])
        ->and(crumbsOfType($env, 'log'))->toBe([]);
});

it('records nothing and sends nothing while disabled', function (): void {
    $t = new FakeTransport;
    $client = fakeSdk($t, ['bliptrace.enabled' => false]);
    DB::select('select 1');

    expect($client->breadcrumbs())->toBe([]);
    reported($t);
    expect($t->sent)->toBe([]);
});

it('sends config tags and facade tags, breadcrumbs and trace', function (): void {
    rebootSdk(['bliptrace.key' => 'k', 'bliptrace.tags' => ['tenant' => 'umpsa']]);
    app()->forgetInstance(Client::class);
    app()->forgetInstance(Reporter::class);
    Bliptrace::clearResolvedInstances();
    $t = new FakeTransport;
    // Keep the container-built config (tags from config), swap only the transport.
    app()->instance(Client::class, new Client(sdkConfig(), $t));

    Bliptrace::setTag('region', 'my');
    Bliptrace::setTags(['shard' => 3]);
    Bliptrace::addBreadcrumb('user', 'clicked pay', ['token' => 'abc'], 'ui');
    Bliptrace::setTrace('trace-1', 'span-1');

    $env = reported($t);
    expect($env['tags'])->toBe(['tenant' => 'umpsa', 'region' => 'my', 'shard' => '3'])
        ->and(crumbsOfType($env, 'user')[0]['data'])->toBe(['token' => '[REDACTED]'])
        ->and($env['trace'])->toBe(['trace_id' => 'trace-1', 'span_id' => 'span-1']);
});
