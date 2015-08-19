<?php

/**
 * Created by JetBrains PhpStorm.
 * User: jimmyszhou
 * Date: 15-3-3
 * Time: 下午3:51
 * To change this template use File | Settings | File Templates.
 * 对应swoole的base模式的server
 */
class SimpleServer extends Swoole\Server implements Swoole\Server\Driver
{
    protected $mode = SWOOLE_BASE;

}
