<?php

namespace Xincheng\Launcher\Handler;

use Xincheng\Launcher\Constants;
use Xincheng\Launcher\Request\RequestContract;
use Xincheng\Launcher\Utils\GenerateUtils;

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
        if (!empty($_SERVER[Constants::SERVER_HTTP_AUTHORIZATION])) {
            $options['headers'][Constants::HTTP_HEADER_AUTHORIZATION] = $_SERVER[Constants::SERVER_HTTP_AUTHORIZATION];
        }

        //request id
        if (empty($_SERVER[Constants::SERVER_X_REQUEST_ID])) {
            $options['headers'][Constants::HTTP_X_REQUEST_ID] = GenerateUtils::uuid();
        } else {
            $options['headers'][Constants::HTTP_X_REQUEST_ID] = $_SERVER[Constants::SERVER_X_REQUEST_ID];
        }

        //运行环境
        if (empty($_SERVER[Constants::SERVER_X_PLATFORM])) {
            $options['headers'][Constants::HTTP_X_PLATFORM] = $request->platform();
        } else {
            $options['headers'][Constants::HTTP_X_PLATFORM] = $_SERVER[Constants::SERVER_X_PLATFORM];
        }

        //租户id
        if (!empty($_SERVER[Constants::SERVER_X_TENANT_ID]) && empty($options['headers'][Constants::HTTP_X_TENANT_ID])) {
            $options['headers'][Constants::HTTP_X_TENANT_ID] = $_SERVER[Constants::SERVER_X_TENANT_ID];
        }

        //user id
        if (!empty($_SERVER[Constants::SERVER_X_USER_ID]) && empty($options['headers'][Constants::HTTP_X_USER_ID])) {
            $options['headers'][Constants::HTTP_X_USER_ID] = $_SERVER[Constants::SERVER_X_USER_ID];
        }
    }
}