<?php

namespace Xincheng\Launcher\Request;

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
        $this->options['headers']['x-tenant-id'] = $tenantId;
    }

    /**
     * 设置用户id
     *
     * @param int $userId 用户id
     * @return void
     */
    public function setUserId(int $userId)
    {
        $this->options['headers']['x-user-id'] = $userId;
    }

    /**
     * 设置body 参数
     *
     * @param mixed $body 消息体
     * @return void
     */
    public function setBody($body)
    {
        $this->options['form_params'] = $body;
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

    public function params(): array
    {
        return $this->params;
    }

    public function options(): array
    {
        return $this->options;
    }
}