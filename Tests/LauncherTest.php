<?php

namespace XinCheng\Launcher\Tests;

use Xincheng\Launcher\Launcher;
use PHPUnit\Framework\TestCase;
use Xincheng\Launcher\Request\WebBaseRequest;

class LauncherTest extends TestCase
{
    private array $config = [
        'services' => [
            ['name' => 'http-test', 'target' => ['http://10.20.7.101/'], 'type' => 'http'],
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

//    public function testNacosService()
//    {
//        $launcher = new Launcher($this->config);
//        $result = $launcher->run(new NacosServiceRequest());
//
//        var_dump($result);
//    }
}

class HttpServiceRequest extends WebBaseRequest
{
    public function server(): string
    {
        return 'http-test';
    }

    public function router(): string
    {
        return '/test.php';
    }

    public function method(): string
    {
        return 'GET';
    }
}

class NacosServiceRequest extends WebBaseRequest
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