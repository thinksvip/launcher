<?php

use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Xincheng\Launcher\Handler\MesNacosServiceHandler;
use Xincheng\Launcher\Request\WebBaseRequest;

/**
 * MesNacosServiceHandlerTest
 *
 * @author: morgan
 * @slice : 2024-07-16 11:26:54
 */
class MesNacosServiceHandlerTest extends TestCase
{
    /**
     * 测试获取nacos实例
     *
     * @return void
     * @throws GuzzleException
     */
    public function testHandle()
    {
        $handler = new MesNacosServiceHandler();

        $result = $handler->handle(new ARequest(), [
            'mes_nacos' => [

            ]
        ]);

        echo $result->getBody();
    }

}

class ARequest extends WebBaseRequest
{
    public function server(): string
    {
        return 'erp-message';
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