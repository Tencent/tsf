<?php

/**
 * Created by JetBrains PhpStorm.
 * User: jimmyszhou
 * Date: 15-3-25
 * Time: 下午2:42
 * To change this template use File | Settings | File Templates.
 */
class ZMQServer extends Swoole\Network\Protocol implements Swoole\Server\Protocol
{

    public function __construct($reciver, $sender, $timeInterval)
    {

    }

    public function onReceive($server, $clientId, $fromId, $data)
    {
    }

    public function onStart($serv, $workerId)
    {
    }

    public function onShutdown($serv, $workerId)
    {
    }

    public function onConnect($server, $fd, $fromId)
    {

    }

    public function onClose($server, $fd, $fromId)
    {

    }

    public function onTask($serv, $taskId, $fromId, $data)
    {

    }

    public function onFinish($serv, $taskId, $data)
    {

    }

    public function onTimer($serv, $interval)
    {

    }
}
