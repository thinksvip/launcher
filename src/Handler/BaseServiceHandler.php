<?php

namespace Xincheng\Launcher\Handler;

use Xincheng\Launcher\CacheInterface;
use Xincheng\Launcher\Exception\ServiceNotFoundException;

/**
 * BaseServiceHandler
 *
 * @author: morgan
 * @slice : 2023-06-27 10:51:11
 */
abstract class BaseServiceHandler implements ServiceHandler
{
    protected ?CacheInterface $cache = null;

    public function setCache(?CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * 获取服务名称
     *
     * @param string $name       服务名称
     * @param array  $properties 配置信息
     * @return array 服务信息
     */
    protected function getService(string $name, array $properties): array
    {
        $services = $properties['services'];

        foreach ($services as $item) {
            if ($item['name'] == $name) {
                return $item;
            }
        }

        throw new ServiceNotFoundException("服务未找到,请检查配置文件");
    }
}