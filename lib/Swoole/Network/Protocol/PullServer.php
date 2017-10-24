<?php

/**
 * Tencent is pleased to support the open source community by making TSF Solution available.
 * Copyright (C) 2017 THL A29 Limited, a Tencent company. All rights reserved.
 * Licensed under the BSD 3-Clause License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * https://opensource.org/licenses/BSD-3-Clause
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
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
    public function onTimer($timer_id, $interval)
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
                //edit by Terry Gao at 2016-12-27
                //由于新版Swoole(1.8+)移除了addtimer方法，改用swoole_server->tick方法替代
                //相应的onTimer回调也稍作调整
                //$serv->addtimer($k);
                $serv->tick($k, function ($timer_id, $params){
                    $this->onTimer($timer_id, $params);
                }, $k);
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
