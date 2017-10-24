<?php
/**
 * Tencent is pleased to support the open source community by making TSF Solution available.
 * Copyright (C) 2017 THL A29 Limited, a Tencent company. All rights reserved.
 * Licensed under the BSD 3-Clause License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * https://opensource.org/licenses/BSD-3-Clause
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
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