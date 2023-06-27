<?php

namespace Xincheng\Launcher\Request;

use Xincheng\Launcher\Constants;

/**
 * WebBaseRequest
 *
 * @author: morgan
 * @slice : 2023-06-26 17:47:16
 */
abstract class WebBaseRequest extends BaseRequest
{
    public function platform(): string
    {
        return Constants::PLATFORM_WEB;
    }
}