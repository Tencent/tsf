<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jimmyszhou
 * Date: 15-7-7
 * Time: 上午11:23
 * To change this template use File | Settings | File Templates.
 */
class TSFUdpServ extends Swoole\Network\Protocol\BaseServer
{

    private $protocal;
    private $route;

    public function onReceive($server, $clientId, $fromId, $data){
        $req = $this -> protocal -> unpackAll($data);
        $conn = $server -> connection_info($clientId, $fromId);

        $this -> onRequest($server, $req, $conn);
    }
    public function setUdp($protocal, $route){
        $this -> protocal = $protocal;
        $this -> route = $route;
    }
    public function onRequest($server, $req, $conn){
        $cmd = $req['cmd'];
        $route = $this -> getRoute() -> getRoute($cmd);
        //陆游失败,命令字校验失败
        if($route['r'] !== 0){
            $data = array('errCode' => -1, 'errMsg' => 'not found class');
            $rsp = $this -> getProtocal() -> packHeader() .
                $this -> getProtocal() -> packBody($data);
            $server -> sendTo($conn['remote_ip'], $conn['remote_port'], $rsp);
        }
        $class = $route['controller'] . 'Controller';
        $fun = 'action' . $route['action'];
        //判断类是否存在
        if (!class_exists($class) || !method_exists($class, $fun)){
            $rsp = $this -> getProtocal() -> packHeader() . $this -> getProtocal() -> packBody(array('errCode' => -1, 'errMsg' => 'not found class'));
            $server->sendto($conn['remote_ip'], $conn['remote_port'], $rsp);
            return;
        };
        $req['protocal'] = $this -> getProtocal();
        $obj = new $class($this -> server,array('request' => $req, 'info' => $conn));
        //代入参数
        $server ->scheduler -> newTask($obj->doFun($fun)) ;
        $server ->scheduler -> run();
    }

    public function onStart($server, $workerId)
    {
        $scheduler = new \Swoole\Coroutine\Scheduler();
        $server ->scheduler = $scheduler;
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
    public function getProtocal(){
        return $this -> protocal;
    }
    public function getRoute(){
        return $this -> route;
    }

}
