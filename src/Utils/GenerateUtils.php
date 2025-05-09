<?php

namespace Xincheng\Launcher\Utils;

/**
 * GenerateUtils
 *
 * @author: morgan
 * @slice : 20230626 17:49:43
 */
class GenerateUtils
{
    /**
     * 生成uuid
     *
     * @return string
     */
    public static function uuid(): string
    {
        return sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}