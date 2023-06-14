<?php

namespace Launcher\request;

use Launcher\request\RequestContract;

/**
 * 基础请求类
 *
 * @author moragn
 * @since 2023-06-14 13:48
 */
abstract class BaseRequest implements RequestContract
{

    public function before(object $context): void
    {

    }
}