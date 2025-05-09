<?php

namespace Xincheng\Launcher\Utils;

/**
 * ArrUtils
 *
 * @author: morgan
 * @slice : 2023-06-28 14:35:06
 */
class ArrUtils
{
    /**
     * 数据合并
     *
     * @param array $a 数组
     * @param array $b 数组
     * @return array 合并
     */
    public static function merge(array $a, array $b): array
    {
        $args = func_get_args();
        $res = array_shift($args);

        while (!empty($args)) {
            foreach (array_shift($args) as $k => $v) {
                if (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = static::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }
}