<?php

namespace Xincheng\Launcher\Handler;

use Xincheng\Launcher\Request\RequestContract;
use Xincheng\Launcher\utils\GenerateUtils;

/**
 * HandlerHelper
 *
 * @author: morgan
 * @slice : 2023-06-26 17:35:36
 */
trait HandlerHelper
{
    /**
     * 请头信息设置
     *
     * @param RequestContract $request 请求
     * @param array           $options 配置信息
     * @return void
     */
    protected function headers(RequestContract $request, array &$options)
    {
        //authorization
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $options['headers']['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
        }

        //request id
        if (empty($_SERVER['HTTP_X_REQUEST_ID'])) {
            $options['headers']['x-request-id'] = GenerateUtils::uuid();
        } else {
            $options['headers']['x-request-id'] = $_SERVER['HTTP_X_REQUEST_ID'];
        }

        //运行环境
        if (empty($_SERVER['HTTP_X_PLATFORM'])) {
            $options['headers']['x-platform'] = $request->platform();
        } else {
            $options['headers']['x-platform'] = $_SERVER['HTTP_X_PLATFORM'];
        }

        //租户id
        if (!empty($_SERVER['HTTP_X_TENANT_ID'])) {
            $options['headers']['x-tenant-id'] = $_SERVER['HTTP_X_TENANT_ID'];
        }

        //user id
        if (!empty($_SERVER['HTTP_X_USER_ID'])) {
            $options['headers']['x-user-id'] = $_SERVER['HTTP_X_USER_ID'];
        }
    }
}