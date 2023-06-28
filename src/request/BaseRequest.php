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