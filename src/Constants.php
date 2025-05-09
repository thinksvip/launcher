<?php

namespace Xincheng\Launcher;

/**
 * Constants
 *
 * @author: morgan
 * @slice : 2023-06-26 17:45:39
 */
class Constants
{
    /**
     * 运行平台 - web
     */
    public const PLATFORM_WEB = 'web';

    /**
     * 运行平台 - console
     */
    public const PLATFORM_CONSOLE = 'console';

    /**
     * http 鉴权头信息
     */
    public const HTTP_HEADER_AUTHORIZATION = "Authorization";

    /**
     * server header Authorization
     */
    public const SERVER_HTTP_AUTHORIZATION = "HTTP_AUTHORIZATION";

    /**
     * http request id
     */
    public const HTTP_X_REQUEST_ID = "x-request-id";

    /**
     * server request id
     */
    public const SERVER_X_REQUEST_ID = "HTTP_X_REQUEST_ID";

    /**
     * http request platform
     */
    public const HTTP_X_PLATFORM = "x-platform";

    /**
     * server request platform
     */
    public const SERVER_X_PLATFORM = "HTTP_X_PLATFORM";

    /**
     * http request tenant id
     */
    public const HTTP_X_TENANT_ID = "x-tenant-id";

    /**
     * server request tenant id
     */
    public const SERVER_X_TENANT_ID = "HTTP_X_TENANT_ID";

    /**
     * http request user id
     */
    public const HTTP_X_USER_ID = "x-user-id";

    /**
     * server request user id
     */
    public const SERVER_X_USER_ID = "HTTP_X_USER_ID";
}