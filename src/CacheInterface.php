<?php

namespace Xincheng\Launcher;

/**
 * CacheInterface
 *
 * @author: morgan
 * @slice : 2023-06-27 10:35:24
 */
interface CacheInterface
{
    /**
     * 是否存在key
     *
     * @param string $key
     * @return bool
     */
    public function hasKey(string $key): bool;

    /**
     * 设置缓存
     *
     * @param string $key      key
     * @param mixed  $value    值
     * @param int    $duration 有效期(秒)
     * @return void
     */
    public function set(string $key, $value, int $duration);

    /**
     * 获取缓存
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key);

    /**
     * 删除缓存
     *
     * @param string $key key
     * @return void
     */
    public function del(string $key);

}