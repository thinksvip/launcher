<?php

namespace Xincheng\Launcher\Service;

/**
 * 服务元数据
 *
 * @author morgan
 * @since 2023-06-13 9:51
 */
interface ServiceMetaContract
{
    /**
     * 获取服务名称
     *
     * @return string 服务名称
     */
    public function getName(): string;

    /**
     * 路由类型
     *
     * @return string static(根据动态配置寻址)|dynamic(注册服务于发现自动寻址)
     */
    public function getType(): string;

}