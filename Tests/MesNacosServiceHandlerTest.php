<?php

namespace Xincheng\Launcher\Tests;

use AlibabaCloud\SDK\Mse\V20190531\Mse;
use PHPUnit\Framework\TestCase;
use Xincheng\Launcher\Exception\ServiceNotFoundException;
use Xincheng\Launcher\Handler\MesNacosServiceHandler;

class MesNacosServiceHandlerTest extends TestCase
{
    public function testCreatesSdkClient(): void
    {
        $handler = new MesNacosServiceHandler();
        $client = $handler->createClient([
            'access_key_id' => 'test-id',
            'access_key_secret' => 'test-secret',
            'endpoint' => 'mse.example.test',
        ]);

        self::assertInstanceOf(Mse::class, $client);
    }

    public function testDiscoversInstanceUsingSdkResponse(): void
    {
        $instance = ['Ip' => '127.0.0.1', 'Port' => 8080];
        $client = $this->createPartialMock(Mse::class, ['callApi']);
        $client->expects(self::once())->method('callApi')
            ->willReturnCallback(function ($params, $request) use ($instance) {
                self::assertSame('ListAnsInstances', $params->action);
                self::assertSame('erp-message', $request->query['ServiceName']);
                self::assertSame('test-instance', $request->query['InstanceId']);
                self::assertSame('test-namespace', $request->query['NamespaceId']);

                return ['body' => ['Data' => [$instance]]];
            });
        $request = new ServiceRequest('erp-message', '/hello');

        $handler = new MesNacosServiceHandler();
        $result = $handler->service($client, [
            'instance_id' => 'test-instance',
            'namespace_id' => 'test-namespace',
        ], $request);

        self::assertSame($instance, $result);
    }

    public function testEmptyInstanceListIsReportedAsUnavailable(): void
    {
        $client = $this->createPartialMock(Mse::class, ['callApi']);
        $client->method('callApi')->willReturn(['body' => ['Data' => []]]);

        $this->expectException(ServiceNotFoundException::class);
        (new MesNacosServiceHandler())->service($client, [
            'instance_id' => 'test-instance',
            'namespace_id' => 'test-namespace',
        ], new ServiceRequest('erp-message', '/hello'));
    }

    public function testLooksForAnAvailableInstanceOnTheNextPage(): void
    {
        $instance = ['Ip' => '127.0.0.1', 'Port' => 8080];
        $client = $this->createPartialMock(Mse::class, ['callApi']);
        $client->expects(self::exactly(2))->method('callApi')
            ->willReturnCallback(function ($params, $request) use ($instance) {
                $nodes = $request->query['PageNum'] == 1
                    ? [['Ip' => 'disabled.test', 'Port' => 80, 'Enabled' => false],
                       ['Ip' => 'unhealthy.test', 'Port' => 80, 'Healthy' => false]]
                    : [$instance];
                return ['body' => ['Data' => $nodes, 'TotalCount' => 3, 'PageSize' => 2]];
            });

        $result = (new MesNacosServiceHandler())->service($client, [
            'instance_id' => 'test-instance',
            'namespace_id' => 'test-namespace',
        ], new ServiceRequest('erp-message', '/hello'));
        self::assertSame($instance, $result);
    }
}
