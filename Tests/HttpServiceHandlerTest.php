<?php

namespace Xincheng\Launcher\Tests;

use Ackintosh\Ganesha\Storage\Adapter\RedisStore;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Xincheng\Launcher\Constants;
use Xincheng\Launcher\Exception\ServiceNotFoundException;
use Xincheng\Launcher\Exception\ServiceRejectedException;
use Xincheng\Launcher\Handler\HttpServiceHandler;
use Xincheng\Launcher\Request\RequestContract;
use Xincheng\Launcher\Service\CircuitBreaker;

class HttpServiceHandlerTest extends TestCase
{
    private const PROPERTIES = [
        'services' => [
            ['name' => 'orders', 'type' => 'http', 'target' => ['https://orders.test']],
        ],
    ];

    public function testClosedCircuitReturnsResponse(): void
    {
        $redis = $this->createMock(RedisStore::class);
        $redis->method('zCard')->willReturn(0);
        $redis->expects($this->once())->method('zAdd');

        $response = new Response(200, [], 'OK');
        $transport = new MockHandler([$response]);
        $handler = $this->createHandler($redis, $transport);

        $this->assertSame($response, $handler->handle(new ServiceRequest('orders', '/health'), self::PROPERTIES));
        $this->assertSame('https://orders.test/health', (string) $transport->getLastRequest()->getUri());
    }

    public function testOpenCircuitRejectsRequestBeforeTransport(): void
    {
        $redis = $this->createMock(RedisStore::class);
        // 10 次成功和 10 次失败达到默认的 50% 熔断阈值。
        $redis->method('zCard')->willReturn(10);
        $redis->method('zRange')->willReturn([time()]);

        $transport = new MockHandler();
        $handler = $this->createHandler($redis, $transport);

        $this->expectException(ServiceRejectedException::class);
        $handler->handle(new ServiceRequest('orders', '/health'), self::PROPERTIES);
    }

    public function testCustomTransportRespectsOpenCircuitAndFallback(): void
    {
        $redis = $this->createMock(RedisStore::class);
        $redis->method('zCard')->willReturn(10);
        $redis->method('zRange')->willReturn([time()]);
        $handler = new HttpServiceHandler(new CircuitBreaker([], $redis));

        $request = new class('orders', '/orders') extends ServiceRequest {
            public function isCircuitBreakerProcess(): bool
            {
                return true;
            }

            public function fallback()
            {
                return 'cached orders';
            }
        };
        $transport = new MockHandler([]);
        $request->options = ['handler' => $transport];

        self::assertSame('cached orders', $handler->handle($request, self::PROPERTIES));
        self::assertNull($transport->getLastRequest());
    }

    public function testCircuitIsSharedAcrossOrdersOfTheSameService(): void
    {
        $redis = $this->createMock(RedisStore::class);
        $redis->method('zCard')->willReturn(0);
        $keys = [];
        $redis->method('zAdd')->willReturnCallback(function ($key) use (&$keys) {
            $keys[] = $key;
            return 1;
        });
        $handler = $this->createHandler($redis, new MockHandler([new Response(), new Response()]));
        foreach ([10, 20] as $id) {
            $request = new ServiceRequest('orders', '/orders/' . $id);
            $request->setParams(['id' => $id]);
            $handler->handle($request, self::PROPERTIES);
        }

        self::assertCount(2, $keys);
        self::assertSame($keys[0], $keys[1]);
    }

    public function testRejectionDoesNotCallUnknownMethodsOnLegacyRequests(): void
    {
        $redis = $this->createMock(RedisStore::class);
        $redis->method('zCard')->willReturn(10);
        $redis->method('zRange')->willReturn([time()]);
        $handler = $this->createHandler($redis, new MockHandler([]));
        $request = $this->createMock(LegacyDynamicRequest::class);
        $request->expects(self::any())->method('server')->willReturn('orders');
        $request->expects(self::any())->method('router')->willReturn('/orders');
        $request->expects(self::any())->method('method')->willReturn('GET');
        $request->expects(self::never())->method('__call');

        $this->expectException(ServiceRejectedException::class);
        $handler->handle($request, self::PROPERTIES);
    }

    public function testUsesOnlyConfiguredHttpTargets(): void
    {
        $properties = self::PROPERTIES;
        $properties['services'][0]['target'] = [false, '', ' ', 'https://orders.test'];
        $transport = new MockHandler([new Response()]);
        $request = new ServiceRequest('orders', '/orders');
        $request->options = ['handler' => $transport];
        $handler = new HttpServiceHandler();
        $handler->handle($request, $properties);
        self::assertSame('orders.test', $transport->getLastRequest()->getUri()->getHost());

        $properties['services'][0]['target'] = [false, '', ' '];
        $this->expectException(ServiceNotFoundException::class);
        $handler->handle($request, $properties);
    }

    public function testMarketCredentialsAndTokenQueryArePreserved(): void
    {
        $server = $_SERVER;
        try {
            $_SERVER[Constants::SERVER_HTTP_AUTHORIZATION] = 'Bearer oms-user';
            $transport = new MockHandler([new Response()]);
            $request = new ServiceRequest('orders', '/token?grant_type=client_credentials');
            $request->setParams(['scope' => 'order list']);
            $request->options = ['handler' => $transport, 'headers' => ['authorization' => 'Basic market']];
            (new HttpServiceHandler())->handle($request, self::PROPERTIES);

            $outgoing = $transport->getLastRequest();
            self::assertSame(['Basic market'], $outgoing->getHeader('Authorization'));
            self::assertSame('grant_type=client_credentials&scope=order+list', $outgoing->getUri()->getQuery());
        } finally {
            $_SERVER = $server;
        }
    }

    public function testForwardsZeroValuedInboundHeaders(): void
    {
        $server = $_SERVER;
        try {
            $headers = [
                Constants::SERVER_HTTP_AUTHORIZATION => Constants::HTTP_HEADER_AUTHORIZATION,
                Constants::SERVER_X_TENANT_ID => Constants::HTTP_X_TENANT_ID,
                Constants::SERVER_X_USER_ID => Constants::HTTP_X_USER_ID,
                Constants::SERVER_X_REQUEST_ID => Constants::HTTP_X_REQUEST_ID,
                Constants::SERVER_X_PLATFORM => Constants::HTTP_X_PLATFORM,
            ];
            foreach ($headers as $serverKey => $header) {
                $_SERVER[$serverKey] = '0';
            }
            $request = new ServiceRequest('orders', '/orders');
            $transport = new MockHandler([new Response()]);
            $request->options = [
                'handler' => $transport,
                'headers' => ['X-Request-Id' => 'caller-id', 'X-Platform' => 'caller-platform'],
            ];

            (new HttpServiceHandler())->handle($request, self::PROPERTIES);
            foreach ($headers as $header) {
                self::assertSame(['0'], $transport->getLastRequest()->getHeader($header));
            }
        } finally {
            $_SERVER = $server;
        }
    }

    private function createHandler(RedisStore $redis, MockHandler $transport): HttpServiceHandler
    {
        $handler = new HttpServiceHandler(new CircuitBreaker([], $redis));
        $handler->clientHandlers->setHandler($transport);
        return $handler;
    }
}

interface LegacyDynamicRequest extends RequestContract
{
    public function __call($name, $arguments);
}
