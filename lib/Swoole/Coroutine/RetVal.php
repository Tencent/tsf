<?php
/**
 * @Author: winterswang
 * @Date:   2015-07-04 22:52:32
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-07-04 23:41:57
 */

namespace Swoole\Coroutine;

class RetVal
{

    protected $info;

    public function __construct($info)
    {

        $this->info = $info;
    }
}