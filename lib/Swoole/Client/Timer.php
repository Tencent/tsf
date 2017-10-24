<?php
/**
 * Tencent is pleased to support the open source community by making TSF Solution available.
 * Copyright (C) 2017 THL A29 Limited, a Tencent company. All rights reserved.
 * Licensed under the BSD 3-Clause License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * https://opensource.org/licenses/BSD-3-Clause
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 */

namespace Swoole\Client;

class Timer
{

    protected static $event = array();
    protected static $isOnTimer = false;

    const CONNECT = 1;
    const RECEIVE = 2;

    const LOOPTIME = 0.5; //设置loop循环的时间片

    /**
     * [add 添加IO事件]
     * @param [type] $socket   [fd]
     * @param [type] $timeout  [second]
     * @param [type] $cli      [description]
     * @param [type] $callback [description]
     */
    public static function add($key, $timeout, $cli, $callback, $params)
    {

        \SysLog::info(__METHOD__ . " key == $key ", __CLASS__);

        self::init();

        $event = array(
            'timeout' => microtime(true) + $timeout,
            'cli' => $cli,
            'callback' => $callback,
            'params' => $params,
        );

        self::$event[$key] = $event;
    }


    public static function del($key)
    {

        \SysLog::info(__METHOD__ . " del event key == $key ", __CLASS__);
        if (isset(self::$event[$key])) {

            unset(self::$event[$key]);
        }
    }

    public static function loop($timer_id)
    {

        \SysLog::info(__METHOD__, __CLASS__);
        /*
            遍历自己的数组，发现时间超过预定时间段，且该IO的状态依然是未回包状态，则走超时逻辑
         */
        if (empty(self::$event)) {
            \SysLog::info(__METHOD__ . " del event swoole_timer_clear == $timer_id ", __CLASS__);
            swoole_timer_clear($timer_id);
        }

        foreach (self::$event as $socket => $e) {

            $now = microtime(true);
            \SysLog::debug(__METHOD__ . " key == $socket  now == $now timeout == " . $e['timeout'], __CLASS__);
            if ($now > $e['timeout']) {
                self::del($socket);
                $cli = $e['cli'];
                $cli->close();
                call_user_func_array($e['callback'], $e['params']);
            }
        }
    }

    /**
     * [init 启动定时器]
     * @return [type] [description]
     */
    public static function init()
    {

        if (!self::$isOnTimer) {

            swoole_timer_tick(1000 * self::LOOPTIME, function ($timer_id) {
                //循环数组，踢出超时情况
                self::loop($timer_id);
                self::$isOnTimer = false;
            });
            self::$isOnTimer = true;
        }
    }

}
