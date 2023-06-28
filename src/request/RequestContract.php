<?php

namespace Xincheng\Launcher\Request;

/**
 * 请求接口
 *
 * 约束请求规范
 *
 * @author morgan
 * @since  2023-06-13 9:44
 */
interface RequestContract
{

    /**
     * 获取服务类型
     *
     * @return string 服务类型
     */
    public function server(): string;

    /**
     * 获取服务路由
     *
     * @return string 服务路由
     */
    public function router(): string;

    /**
     * 请求方法
     *
     * @return string
     */
    public function method(): string;

    /**
     * 选项信息
     *
     * @return array 请求配置信息
     */
    public function options(): array;

    /**
     * 前置设置服务
     *
     * @param object $context 上下文
     * @return void
     */
    public function before(object $context): void;

    /**
     * 运行平台
     *
     * @return string
     */
    public function platform(): string;

    /**
     * 请求参数
     *
     * @return array
     */
    public function params(): array;

    /**
     * 构建请求参数
     *
     * @return string 请求参数
     */
    public function buildQuery(): string;
}