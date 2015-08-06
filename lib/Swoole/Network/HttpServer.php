<?php
/**
 * Created by PhpStorm.
 * User: chalesi
 * Date: 14-11-12
 * Time: 下午4:27
 */
namespace Swoole\Network;

class HttpServer extends \Swoole\Network\TcpServer
{
    public function init()
    {
        $this->enableHttp = true;
    }
}