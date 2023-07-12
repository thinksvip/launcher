<?php

namespace Xincheng\Launcher\Handler;

use Xincheng\Launcher\CacheInterface;

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
}