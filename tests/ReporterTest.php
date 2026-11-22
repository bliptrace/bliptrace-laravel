<?php

declare(strict_types=1);

use G8Scope\Laravel\Reporter;
use G8Scope\Php\Client;
use G8Scope\Php\Config;
use G8Scope\Php\Transport\Transport;

final class FakeTransport implements Transport
{
    public array $sent = [];

    public function send(string $endpoint, string $key, array $envelope, int $timeout): void
    {
        $this->sent[] = compact('endpoint', 'key', 'envelope');
    }
}

function reporterWith(FakeTransport $t): Reporter
{
    $client = new Client(new Config(key: 'k', endpoint: 'http://api', basePath: base_path()), $t);

    return new Reporter(app(), $client);
}

it('defers the send until the app terminates', function (): void {
    $t = new FakeTransport;
    reporterWith($t)->capture(new RuntimeException('boom'));

    expect($t->sent)->toBeEmpty();      // not sent during the request
    app()->terminate();
    expect($t->sent)->toHaveCount(1)
        ->and($t->sent[0]['envelope']['exception']['type'])->toBe('RuntimeException')
        ->and($t->sent[0]['envelope']['sdk']['name'])->toBe('g8scope-php');
});

it('publishes a config + registers the provider client', function (): void {
    expect(config('bliptrace.endpoint'))->not->toBeNull()
        ->and(app(Client::class))->toBeInstanceOf(Client::class);
});

it('respects the master kill switch', function (): void {
    config()->set('bliptrace.enabled', false);
    // rebuild client with new config
    app()->forgetInstance(Client::class);
    $t = new FakeTransport;
    $client = new Client(new Config(key: 'k', enabled: false), $t);
    (new Reporter(app(), $client))->capture(new Exception('x'));
    app()->terminate();
    expect($t->sent)->toBeEmpty();
});
