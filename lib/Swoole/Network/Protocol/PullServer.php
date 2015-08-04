<?php

/**
 * Created by JetBrains PhpStorm.
 * User: jimmyszhou
 * Date: 15-3-18
 * Time: 下午3:31
 * To change this template use File | Settings | File Templates.
 * 主动通过消息通道进行拉取的server,包括原生msg_queue和zmq
 */
class PullServer extends Swoole\Network\Protocol implements Swoole\Server\Protocol
{
    private $handles;
    private $fds;
    private $timeJobs;
    //初始化,监听多个不同的IPC
    /**
     * @param $conf = array('projectName' => array('timeInterval' => 100,
     *                                             'res' => $obj))
     */
    public function __construct($conf)
    {
        if (is_array($conf)) {
            foreach ($conf as $k => $v) {
                $this->timeJobs[(int)$v['timeInterval']] = $v['res'];
            }
        }
        //$this -> init();
    }

    public function init()
    {
        //$this -> timePoll();
    }

    public function onReceive($server, $clientId, $fromId, $data)
    {
    }

    public function onStart($serv, $workerId)
    {
        $this->timePoll($serv);
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

    //启动定时期,监听IPC通道
    public function onTimer($serv, $interval)
    {
        $res = $this->timeJobs[$interval];
        if ($data = $res->recv()) {
            $this->onReceive($serv, $res->key, $res->key, $data);
        }
    }

    //设置事件接口
    public function setHandle($fun, $type = 'def')
    {
        $this->handles[$type] = $fun;
    }

    public function getHandle($type = 'def')
    {
        return isset($this->handles[$type]) ? $this->handles[$type] : null;
    }

    //要监听的fd
    public function setFd($fd, $type = 'def')
    {
        $this->fds[$type] = $fd;
    }

    public function getFd($type = 'def')
    {
        return isset($this->fds[$type]) ? $this->fds[$type] : -1;
    }

    //要监听的使用定时器
    private function timePoll($serv)
    {
        if (is_array($this->timeJobs)) {
            foreach ($this->timeJobs as $k => $v) {
                $serv->addtimer($k);
            }
        }
    }

    public function pepoll($opt = 'ADD', $type = 'def', $event = SWOOLE_EVENT_READ)
    {
        $fd = $this->getFd($type);
        if ($fd === -1) return false;
        $readHandle = $this->getHandle('read');
        $writeHandle = $this->getHandle('write');
        if ($writeHandle === null && $readHandle === null) return false;
        switch ($opt) {
            case 'ADD' : {
                $ret = swoole_event_add($fd, $readHandle, $writeHandle, $event);
                break;
            }
            case 'MOD' : {
                $ret = swoole_event_set($fd, $readHandle, $writeHandle, $event);
                break;
            }
            case 'DEL' : {
                $ret = swoole_event_del($fd, $readHandle, $writeHandle, $event);
                break;
            }
            default : {
                return false;
                break;
            }
        }
        return $ret;
    }
}
