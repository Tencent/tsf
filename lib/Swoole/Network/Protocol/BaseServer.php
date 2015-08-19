<?php
/**
 * @Author: chalesi
 * @Date:   2015-05-29 22:21:14
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-06-28 22:25:36
 */

namespace Swoole\Network\Protocol;

use Swoole;

class BaseServer extends Swoole\Network\Protocol implements Swoole\Server\Protocol
{
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

    public function onRequest($request, $response)
    {

    }

    /**
     * [onHttpWorkInit http svr worker init]
     * @param  [type] $request  [description]
     * @param  [type] $response [description]
     * @return [type]           [description]
     */
    public function onHttpWorkInit($request, $response)
    {

    }
}