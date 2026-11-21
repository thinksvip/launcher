<?php

namespace Xincheng\Launcher\Tests;

use Ackintosh\Ganesha\Exception\RejectedException;
use Ackintosh\Ganesha\Storage\Adapter\RedisStore;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Xincheng\Launcher\Service\CircuitBreaker;

class CircuitBreakerTest extends TestCase
{
    public function testCountsServerErrorsRegardlessOfHttpErrorsAndPreservesTransferExceptions(): void
    {
        $redis = $this->createMock(RedisStore::class);
        $redis->method('zCard')->willReturn(0);
        $recorded = [];
        $redis->method('zAdd')->willReturnCallback(function ($key) use (&$recorded) {
            $recorded[] = $key;
            return 1;
        });

        $error = new ConnectException('network unavailable', new Request('GET', 'https://orders.test/'));
        $transport = new MockHandler([
            new Response(503),
            new Response(302, ['Location' => '/upstream']),
            new Response(502),
            $error,
            new Response(404),
        ]);
        $stack = (new CircuitBreaker([], $redis))->getClientHandlersWithGuzzleMiddleware();
        $stack->setHandler($transport);
        $client = new Client(['handler' => $stack, 'base_uri' => 'https://orders.test', 'ganesha.service_name' => 'orders']);

        self::assertSame(503, $client->get('/', ['http_errors' => false])->getStatusCode());
        try {
            $client->get('/');
            self::fail('Expected HTTP 502 to throw');
        } catch (ServerException $e) {
            self::assertSame(502, $e->getResponse()->getStatusCode());
        }
        try {
            $client->get('/');
            self::fail('Expected the connection error');
        } catch (ConnectException $e) {
            self::assertSame($error, $e);
        }
        try {
            $client->get('/');
            self::fail('Expected HTTP 404 to throw');
        } catch (ClientException $e) {
            self::assertSame(404, $e->getResponse()->getStatusCode());
        }
        self::assertSame([
            'ganesha_orders_failure',
            'ganesha_orders_failure',
            'ganesha_orders_failure',
            'ganesha_orders_success',
        ], $recorded);
    }

    public function testConfiguredFailureRateOpensCircuitAndHalfOpenAdmitsProbe(): void
    {
        $redis = $this->createMock(RedisStore::class);
        $counts = ['ganesha_orders_failure' => 1, 'ganesha_orders_success' => 1];
        $lastFailure = time();
        $redis->method('zCard')->willReturnCallback(function ($key) use (&$counts) {
            return $counts[$key] ?? 0;
        });
        $redis->method('zRange')->willReturnCallback(function () use (&$lastFailure) {
            return [$lastFailure];
        });
        $redis->method('zRemRangeByScore')->willReturnCallback(function ($key, $start, $cutoff) {
            self::assertEqualsWithDelta(microtime(true) - 13, $cutoff, 1.0);
            return 0;
        });

        $breaker = new CircuitBreaker([
            'minimumRequests' => 2,
            'failureRateThreshold' => 80,
            'timeWindow' => 13,
            'intervalToHalfOpen' => 3,
        ], $redis);
        $transport = new MockHandler([new Response(200), new Response(204)]);
        $stack = $breaker->getClientHandlersWithGuzzleMiddleware();
        $stack->setHandler($transport);
        $client = new Client(['handler' => $stack, 'base_uri' => 'https://orders.test', 'ganesha.service_name' => 'orders']);

        // 1/2 fails, below 80%. With 4/5 failures the next call is rejected before transport.
        self::assertSame(200, $client->get('/')->getStatusCode());
        $counts['ganesha_orders_failure'] = 4;
        try {
            $client->get('/');
            self::fail('Expected an open circuit');
        } catch (RejectedException $e) {
            self::assertSame(1, $transport->count());
        }

        $lastFailure -= 4;
        self::assertSame(204, $client->get('/')->getStatusCode());
    }
}
