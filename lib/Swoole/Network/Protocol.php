<?php
namespace Swoole\Network;

/**
 * 协议基类，实现一些公用的方法
 * @package Swoole\Network
 */
class Protocol
{
    public $server;

    function __construct()
    {
        $this->init();
    }

    public function init()
    {

    }

    /**
     * 打印Log信息
     * @param $msg
     * @param string $type
     */
    public function log($msg)
    {
        $log = "[" . date("Y-m-d G:i:s") . " " . floor(microtime() * 1000) . "]" . $msg;
        echo $log, NL;
    }

    public function setServer($server)
    {
        $this->server = $server;
    }
}