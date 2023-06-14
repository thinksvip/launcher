<?php

namespace Launcher\Tests;

use GuzzleHttp\Exception\GuzzleException;
use Launcher\Handler\HttpServiceHandler;
use Launcher\request\RequestContract;
use PHPUnit\Framework\TestCase;

/**
 * Http服务处理器测试
 *
 * @author mogran
 * @since 2023-06-14 11:26
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

class BRequest implements RequestContract
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

    public function options(): array
    {
        return [];
    }

    public function before(object $context): void
    {

    }
}