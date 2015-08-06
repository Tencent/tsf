<?php
/**
 * @Author: winterswang
 * @Date:   2015-07-04 22:43:59
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-07-04 23:40:37
 */

namespace Swoole\Coroutine;

class SysCall
{

    public static function end($words)
    {
        return new RetVal($words);
    }
}