<?php

namespace Xincheng\Launcher\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Xincheng\Launcher\CacheInterface;
use Xincheng\Launcher\Constants;
use Xincheng\Launcher\Exception\ServiceNotFoundException;
use Xincheng\Launcher\Handler\NacosServiceHandler;

class NacosServiceHandlerTest extends TestCase
{
    public function serviceTargets(): array
    {
        return [
            'configured registry name' => [['erp-auth'], 'erp-auth'],
            'empty environment value' => [[''], 'auth'],
            'no configured target' => [[], 'auth'],
        ];
    }

    /**
     * @dataProvider serviceTargets
     */
    public function testHandle(array $targets, string $registryName): void
    {
        $config = ['host' => 'http://registry.test:8848'];
        $auth = (object) ['accessToken' => 'test-token'];
        $handler = $this->getMockBuilder(NacosServiceHandler::class)
            ->onlyMethods(['authorizeRequest', 'serviceInstants'])
            ->getMock();
        $handler->expects(self::once())->method('authorizeRequest')->with($config)->willReturn($auth);
        $handler->expects(self::once())->method('serviceInstants')
            ->with($registryName, self::anything(), $auth)
            ->willReturn([
                (object) ['ip' => 'unhealthy.test', 'port' => 8080, 'healthy' => false],
                (object) ['ip' => 'disabled.test', 'port' => 8080, 'enabled' => false],
                (object) ['ip' => '127.0.0.1', 'port' => 8080],
            ]);

        $transport = new MockHandler([new Response(200, [], 'hello')]);
        $request = new ServiceRequest('auth', '/hello');
        $request->options = ['handler' => $transport];
        $request->setTenantId(33);
        $server = $_SERVER;
        try {
            $_SERVER[Constants::SERVER_X_TENANT_ID] = '24';
            $_SERVER[Constants::SERVER_X_USER_ID] = '12';
            $result = $handler->handle($request, [
                'services' => [['name' => 'auth', 'type' => 'nacos', 'target' => $targets]],
                'nacos' => $config,
            ]);
        } finally {
            $_SERVER = $server;
        }

        self::assertSame('hello', (string) $result->getBody());
        self::assertSame('127.0.0.1:8080', $transport->getLastRequest()->getUri()->getAuthority());
        self::assertSame('/hello', $transport->getLastRequest()->getUri()->getPath());
        self::assertSame('33', $transport->getLastRequest()->getHeaderLine(Constants::HTTP_X_TENANT_ID));
        self::assertSame('12', $transport->getLastRequest()->getHeaderLine(Constants::HTTP_X_USER_ID));
    }

    public function testTokenExpiringDuringCacheReadIsRenewed(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('hasKey')->willReturn(true);
        $cache->method('get')->willReturn(false);
        $auth = (object) ['accessToken' => 'new-token', 'tokenTtl' => 120];
        $cache->expects(self::once())->method('set')->with('nacos:token:http://registry.test', $auth, 120);

        $handler = $this->createPartialMock(NacosServiceHandler::class, ['authorizeRequest', 'serviceInstants']);
        $handler->expects(self::once())->method('authorizeRequest')->willReturn($auth);
        $handler->expects(self::once())->method('serviceInstants')->with('auth', self::anything(), $auth)
            ->willReturn([(object) ['ip' => '127.0.0.1', 'port' => 8080]]);
        $handler->setCache($cache);
        $request = new ServiceRequest('auth', '/hello');
        $request->options = ['handler' => new MockHandler([new Response()])];
        $response = $handler->handle($request, [
            'services' => [['name' => 'auth', 'type' => 'nacos', 'target' => []]],
            'nacos' => ['host' => 'http://registry.test'],
        ]);
        self::assertSame(200, $response->getStatusCode());
    }

    public function testDoesNotSendToUnavailableInstances(): void
    {
        $handler = $this->createPartialMock(NacosServiceHandler::class, ['authorize', 'serviceInstants', 'execute']);
        $handler->method('authorize')->willReturn((object) []);
        $handler->method('serviceInstants')->willReturn([
            (object) ['ip' => 'unhealthy.test', 'port' => 80, 'healthy' => false],
            (object) ['ip' => 'disabled.test', 'port' => 80, 'enabled' => false],
        ]);
        $handler->expects(self::never())->method('execute');

        $this->expectException(ServiceNotFoundException::class);
        $handler->handle(new ServiceRequest('auth', '/hello'), [
            'services' => [['name' => 'auth', 'type' => 'nacos', 'target' => []]],
        ]);
    }
}
