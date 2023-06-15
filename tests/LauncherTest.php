<?php

namespace XinCheng\Launcher\Tests;

use Xincheng\Launcher\Launcher;
use Xincheng\Launcher\Request\BaseRequest;
use PHPUnit\Framework\TestCase;

class LauncherTest extends TestCase
{
    private array $config = [
        'services' => [
            ['name' => 'nacos-test', 'target' => ['http://10.20.7.100:8848'], 'type' => 'http'],
            ['name' => 'erp-auth', 'target' => [], 'type' => 'nacos']
        ],
        'nacos' => [
            'host' => '10.20.7.100:8848'
        ]
    ];

    /**
     * 测试http 服务器
     *
     * @return void
     */
    public function testHttpService()
    {
        $launcher = new Launcher($this->config);
        $result = $launcher->run(new HttpServiceRequest());

        echo $result->getBody();
    }

    public function testNacosService()
    {
        $launcher = new Launcher($this->config);
        $result = $launcher->run(new NacosServiceRequest());

        var_dump($result);
    }
}

class HttpServiceRequest extends BaseRequest
{

    public function server(): string
    {
        return 'nacos-test';
    }

    public function router(): string
    {
        return '/nacos/v1/ns/instance/list?serviceName=erp-auth';
    }

    public function method(): string
    {
        return 'GET';
    }
}

class NacosServiceRequest extends BaseRequest
{

    public function server(): string
    {
        return 'erp-auth';
    }

    public function router(): string
    {
        return '/hello';
    }

    public function method(): string
    {
        return 'GET';
    }
}