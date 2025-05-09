<?php

namespace XinCheng\Launcher\Tests;

use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Xincheng\Launcher\Handler\NacosServiceHandler;
use Xincheng\Launcher\Request\RequestContract;
use Xincheng\Launcher\Request\WebBaseRequest;

/**
 * Nacos服务处理器测试
 *
 * @author mogran
 * @since 2023-06-14 09:19
 */
class NacosServiceHandlerTest extends TestCase
{
    /**
     * 测试获取nacos实例
     *
     * @return void
     * @throws GuzzleException
     */
    public function testHandle()
    {
        $handler = new NacosServiceHandler();

        $result = $handler->handle(new ARequest(), [
            'nacos' => [
                'host' => 'http://10.20.7.100:8848'
            ]
        ]);

        echo $result->getBody();
    }

}

class ARequest extends WebBaseRequest
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