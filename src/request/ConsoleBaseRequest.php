<?php

namespace Xincheng\Launcher\Request;

use Xincheng\Launcher\Constants;

/**
 * ConsoleBaseRequest
 *
 * @author: morgan
 * @slice : 2023-06-26 17:45:03
 */
abstract class ConsoleBaseRequest extends BaseRequest
{
    public function platform(): string
    {
        return Constants::PLATFORM_CONSOLE;
    }
}