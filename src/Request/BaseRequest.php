<?php

namespace Xincheng\Launcher\Request;

use GuzzleHttp\RequestOptions;
use Xincheng\Launcher\Constants;

/**
 * 基础请求类
 *
 * @author moragn
 * @since  2023-06-14 13:48
 */
abstract class BaseRequest implements RequestContract
{
    /**
     * @var array 参数
     */
    public array $params = [];

    public array $options = [];

    public string $platform = Constants::PLATFORM_WEB;

    public function before(object $context): void
    {

    }

    /**
     * 设置租户id
     *
     * @param int $tenantId 租户id
     * @return void
     */
    public function setTenantId(int $tenantId)
    {
        $this->options['headers'][Constants::HTTP_X_TENANT_ID] = $tenantId;
    }

    /**
     * 设置用户id
     *
     * @param int $userId 用户id
     * @return void
     */
    public function setUserId(int $userId)
    {
        $this->options['headers'][Constants::HTTP_X_USER_ID] = $userId;
    }

    /**
     * 设置body 参数
     *
     * @param mixed $body 消息体
     * @return void
     */
    public function setBody($body)
    {
        $this->options[RequestOptions::JSON] = $body;
    }

    /**
     * 设置平台信息
     *
     * @param string $platform 平台
     * @return void
     */
    public function setPlatform(string $platform)
    {
        $this->platform = $platform;
    }

    /**
     * 设置params参数
     *
     * @param array $params 平台
     * @return void
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * 获取
     *
     * @return string
     */
    public function buildQuery(): string
    {
        return http_build_query($this->params());
    }

    /**
     * url 参数
     *
     * @return array
     */
    public function params(): array
    {
        return $this->params;
    }

    /**
     * 请求配置
     *
     * @return array
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * 平台信息
     *
     * @return string
     */
    public function platform(): string
    {
        return $this->platform;
    }
}