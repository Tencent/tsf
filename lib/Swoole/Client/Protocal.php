<?php
/**
 * Tencent is pleased to support the open source community by making TSF Solution available.
 * Copyright (C) 2017 THL A29 Limited, a Tencent company. All rights reserved.
 * Licensed under the BSD 3-Clause License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * https://opensource.org/licenses/BSD-3-Clause
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 */

// 增加命名空间
namespace Swoole\Client;

//require_once "Base.php";

class Protocal extends Base
{

    public $host;
    public $port;
    public $data;
    public $timeout;
    public $protocolType;

    public function __construct($host, $port, $data, $timeout, $protocolType)
    {

    }

    /**
     * [sendData 发包，子类继承]
     * @param  callable $callback [description]
     * @return [type]             [description]
     */
    public function send(callable $callback)
    {

        /*
            1.发包协议可以是UDP/TCP/HTTP
            2.本函数负责发包
            3.回调到本地函数，解包，再回调给协程调度器
         */
    }

    /**
     * [unPackRsp 解包]
     * @return [type] [description]
     */
    public function unPackRsp($r, $k, $calltime, $data)
    {

    }

    /**
     * [createClient description]
     * @return [type] [description]
     */
    public function createClient($ip, $port, $data, $timeout, $protocolType)
    {
        switch ($protocolType) {
            case 'udp':
                $client = new UDP($ip, $port, $data, $timeout);
                break;
            case 'tcp':
                $client = new TCP($ip, $port, $data, $timeout);
                break;
            default:
                \SysLog::error(__METHOD__ . " protocolType valid ==> $protocolType", __CLASS__);
                return false;
                break;
        }

        return $client;
    }
}