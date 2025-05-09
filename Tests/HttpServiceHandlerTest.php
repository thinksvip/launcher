<?php

namespace Xincheng\Launcher\Tests;

use GuzzleHttp\Exception\GuzzleException;

use PHPUnit\Framework\TestCase;
use Xincheng\Launcher\Handler\HttpServiceHandler;
use Xincheng\Launcher\Request\WebBaseRequest;

/**
 * Http服务处理器测试
 *
 * @author mogran
 * @since  2023-06-14 11:26
 */
class HttpServiceHandlerTest extends TestCase
{
    /**
     * @throws GuzzleException
     */
    public function testHandle()
    {
        $handler = new HttpServiceHandler();

        $result = $handler->handle(new BRequest(), []);

        echo $result->getBody();
    }
}

class BRequest extends WebBaseRequest
{
    public function server(): string
    {
        return 'http://10.20.7.100:8848';
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