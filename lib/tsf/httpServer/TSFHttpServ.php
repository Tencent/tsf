<?php
/**
 * @Author: markyuan
 * @Date:   2015-07-03 17:22:00
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-07-03 18:03:34
 */

class TSFHttpServ extends Swoole\Network\Protocol\BaseServer{

    public function onRequest($request, $response) {

        //统一进行路由和数据的预处理
        $req = HttpHelper::httpReqHandle($request);
        SysLog::info(__METHOD__.print_r($req,true),__CLASS__);
        if($req['r'] === HttpHelper::HTTP_ERROR_URI){
            $response -> status(404);
            //todo:log
            $response -> end("not found");
            return;
        };

        SysLog::info(__METHOD__.'  '.__LINE__ . " REQUEST IS ".print_r($req,true),__CLASS__);
        $class = $req['route']['controller']. 'Controller';
        $fun= 'action'.$req['route']['action'];
        //判断类是否存在
        if (! class_exists($class)  || !method_exists(($class),($fun))) {
            $response -> status(404);
            SysLog::error(__METHOD__." class or fun not found class == $class fun == $fun",__CLASS__);
            $response -> end("uri not found");
            return;
        };

        $obj = new $class($this -> server,array('request' => $req['request'], 'response' => $response),$request->fd);
        //代入参数
        $request ->scheduler -> newTask($obj->$fun()) ;
        $request ->scheduler -> run();
    }


    /**
     * [onStart 协程调度器单例模式]
     * @return [type] [description]
     */
    public function onHttpWorkInit($request, $response){

        $scheduler = new \Swoole\Coroutine\Scheduler();
        $request ->scheduler = $scheduler;
    }

}
