<?php

namespace Xincheng\Launcher\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Xincheng\Launcher\CacheInterface;
use Xincheng\Launcher\Exception\ServiceNotFoundException;
use Xincheng\Launcher\Launcher;

class LauncherTest extends TestCase
{
    /** @dataProvider cacheInjectionModes */
    public function testDiscoveryUsesLegacyCache(bool $useSetter): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects(self::once())->method('hasKey')->with('nacos:token:http://registry.test')
            ->willThrowException(new \RuntimeException('Reached discovery cache'));
        $properties = [
            'services' => [['name' => 'orders', 'type' => 'nacos', 'target' => []]],
            'nacos' => ['host' => 'http://registry.test'],
        ];
        $launcher = new Launcher($properties, $useSetter ? null : $cache);
        if ($useSetter) {
            $launcher->setCache($cache);
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Reached discovery cache');
        $launcher->run(new ServiceRequest('orders', '/'));
    }

    public static function cacheInjectionModes(): array
    {
        return [[false], [true]];
    }

    public function testRedisIsOnlyRequestedWhenEnablingCircuitBreaker(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)
            ->onlyMethods(['hasKey', 'get', 'set', 'del'])->addMethods(['getRedis'])->getMock();
        $cache->expects(self::once())->method('getRedis');
        $launcher = new Launcher([], $cache);
        $launcher->configuration(['circuitbreaker' => ['open' => true]]);
        $launcher->setCache($cache);
    }

    public function testCacheCanBeRetriedAfterRedisConnectionFails(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)
            ->onlyMethods(['hasKey', 'get', 'set', 'del'])->addMethods(['getRedis'])->getMock();
        $attempts = 0;
        $cache->expects(self::exactly(2))->method('getRedis')->willReturnCallback(function () use (&$attempts) {
            if (++$attempts === 1) {
                throw new \RuntimeException('Redis connection failed');
            }
            return null;
        });
        $launcher = new Launcher(['circuitbreaker' => ['open' => true]]);
        try {
            $launcher->setCache($cache);
            self::fail('Expected Redis connection to fail');
        } catch (\RuntimeException $e) {
            self::assertSame('Redis connection failed', $e->getMessage());
        }
        $launcher->setCache($cache);
    }

    public function testDefaultConfigurationHasNoServices(): void
    {
        $launcher = new Launcher();

        $this->expectException(ServiceNotFoundException::class);
        $launcher->run(new ServiceRequest('unknown', '/'));
    }

    public function testHttpService(): void
    {
        $launcher = new Launcher([
            'services' => [
                ['name' => 'http-test', 'target' => ['http://service.test'], 'type' => 'http'],
            ],
        ]);
        $request = new ServiceRequest('http-test', '/test.php');
        $handler = new MockHandler([new Response(200, [], 'ok')]);
        $request->options = ['handler' => $handler];
        $request->setParams(['id' => 42]);

        $result = $launcher->run($request);

        self::assertSame('ok', (string) $result->getBody());
        self::assertSame('http://service.test/test.php?id=42', (string) $handler->getLastRequest()->getUri());
    }

    public function testLoadServiceAfterConstructionUsesTheNewTarget(): void
    {
        $launcher = new Launcher();
        $launcher->loadService([['name' => 'orders', 'type' => 'http', 'target' => ['http://orders.test']]]);
        $request = new ServiceRequest('orders', '/orders');
        $transport = new MockHandler([new Response()]);
        $request->options = ['handler' => $transport];

        $launcher->run($request);
        self::assertSame('http://orders.test/orders', (string) $transport->getLastRequest()->getUri());
    }
}
