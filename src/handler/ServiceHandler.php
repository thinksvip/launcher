<?php

namespace Launcher\Handler;

use Launcher\request\RequestContract;

/**
 * 服务处理器
 *
 * @author morgan
 * @since 2023-06-13 14:34
 */
interface ServiceHandler
{

    /**
     * 处理器方法
     *
     * @param RequestContract $request 请求信息
     * @param array $properties 配置信息
     * @return mixed
     */
    public function handle(RequestContract $request, array $properties);

    /**
     * 获取处理器类型
     *
     * @return string 处理器类型
     */
    public function type(): string;
}