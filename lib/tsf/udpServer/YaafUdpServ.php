<?php

/**
 * @Author: mickyyxchen
 * @Date:   2015-05-19 21:35:55
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-07-01 11:22:30
 * 支持yaaf协议的udp server
 * controller需继承yaafUdpController
 * 暂时没封装udpController 有需要在抽象吧
 */
class YaafUdpServ extends Swoole\Network\Protocol\BaseServer
{

    public function onReceive($server, $clientId, $fromId, $data)
    {
		$data = Yaaf::unpackAll($data);
        SysLog::notice(__METHOD__ . " fd = $clientId  fromId = $fromId data = " . print_r($data, true), __CLASS__);
        $info = $server->connection_info($clientId, $fromId);

        //yaaf 协议 路由
        $req = YaafHelper::yaafReqHandle($data);
        SysLog::info(__METHOD__ . print_r($req, true), __CLASS__);

        //路由失败 直接返回错误
        if ($req['r'] === YaafHelper::YAAF_ERROR_CMD) {
            //todo 协议搞成yaaf
            $yaaf_data = Yaaf::packHeader() . Yaaf::packBody(array('errCode' => -1, 'errMsg' => 'not found class'));
            $server->sendto($info['remote_ip'], $info['remote_port'], $yaaf_data);
            return;
        }
        $class = $req['route']['controller']. 'Controller';
        $fun= 'action'.$req['route']['action'];
        //判断类是否存在
        if (!class_exists($class) || !method_exists($class, $fun)){
            SysLog::error(__METHOD__ . print_r($req, true), __CLASS__);
            $yaaf_data = Yaaf::packHeader() . Yaaf::packBody(array('errCode' => -1, 'errMsg' => 'not found class'));
            $server->sendto($info['remote_ip'], $info['remote_port'], $yaaf_data);
            return;
        };
        $obj = new $class($this -> server,array('request' => $data, 'info' => $info), $clientId);
        //代入参数
        $server ->scheduler -> newTask($obj->doFun($fun)) ;
        $server ->scheduler -> run();
    }

    public function onTask($server, $taskId, $fromId, $data)
    {
        $task = unserialize($data);
        $task->onTask();
        $server->finish(serialize($task));
        return;
    }

    public function onFinish($server, $taskId, $data)
    {
        $task = unserialize($data);
        //自己预设了回调函数，则调用预设的，没有则走默认的task对象的onFinish函数
        if (is_object($task->obj) && isset($task->func)) {
            //TODO 判断task任务是否正确处理结果，执行预定onFinish函数时，携带上执行状态和执行结果
            $task->obj->{$task->func}(0, $data);
        } else {
            $task->onFinish();
        }
    }

    public function onTimer($serv, $interval)
    {
        //TODO 基于静态类，完成时间点和执行实例的映射关系
        $rets = Timer::getFun($interval);
        //执行定时程序
        foreach ($rets as $ret) {
            $ret[0]->$ret[1]();
        }
    }

    /**
     * [onStart 实例一个mysqli]
     * @param  [type] $serv     [description]
     * @param  [type] $workerId [description]
     * @return [type]           [description]
     */
    public function onStart($serv, $workerId)
    {
        $serv->mysqli = new mysqli();
        $scheduler = new Scheduler();
        $serv ->scheduler = $scheduler;
    }

    /**
     * [onParse description]
     * @param  [type] $serv     [description]
     * @param  [type] $workerId [description]
     * @return [type]           [description]
     */
    public function onParse($data)
    {
        /*
        serv onReceive data parse
         */
        $rsp = Yaaf::unpackAll($data);
        return $rsp;
    }
}

?>