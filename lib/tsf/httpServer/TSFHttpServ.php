<?php

/**
 * Tencent is pleased to support the open source community by making TSF Solution available.
 * Copyright (C) 2017 THL A29 Limited, a Tencent company. All rights reserved.
 * Licensed under the BSD 3-Clause License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * https://opensource.org/licenses/BSD-3-Clause
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 */
class TSFHttpServ extends Swoole\Network\Protocol\BaseServer
{

    public function onRequest($request, $response)
    {

        //SysLog::debug(__METHOD__ . print_r($request,true), __CLASS__);
        //统一进行路由和数据的预处理
        $req = HttpHelper::httpReqHandle($request);
        // SysLog::info(__METHOD__.print_r($req,true),__CLASS__);
        if ($req['r'] === HttpHelper::HTTP_ERROR_URI) {
            $response->status(404);
            //todo:log
            $response->end("not found");
            return;
        };

        //SysLog::info(__METHOD__.'  '.__LINE__ . " REQUEST IS ".print_r($req,true),__CLASS__);
        $class = $req['route']['controller'] . 'Controller';
        $fun = 'action' . $req['route']['action'];
        //判断类是否存在
        if (!class_exists($class) || !method_exists(($class), ($fun))) {
            $response->status(404);
            SysLog::error(__METHOD__ . " class or fun not found class == $class fun == $fun", __CLASS__);
            $response->end("uri not found");
            return;
        };

        $obj = new $class($this->server, array('request' => $req['request'], 'response' => $response), $request->fd);
        //代入参数
        $request->scheduler->newTask($obj->$fun());
        $request->scheduler->run();
    }


    /**
     * [onStart 协程调度器单例模式]
     * @return [type] [description]
     */
    public function onStart($server, $workerId)
    {

        $scheduler = new \Swoole\Coroutine\Scheduler();
        $server->scheduler = $scheduler;
    }
}
