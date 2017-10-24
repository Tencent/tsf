<?php

/**
 * Tencent is pleased to support the open source community by making TSF Solution available.
 * Copyright (C) 2017 THL A29 Limited, a Tencent company. All rights reserved.
 * Licensed under the BSD 3-Clause License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * https://opensource.org/licenses/BSD-3-Clause
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 */
class Controller
{
    protected $server; //启动controller的server实例
    protected $argv = array();
    protected $request;
    protected $fd;
    protected $from_fd;

    /**
     * 为了兼容，fd可以不传
     * @param [type] $pbData [description]
     */

    function __construct($server, $argv = array(), $fd = 0, $from_fd = 0)
    {


        $this->server = $server;
        $this->argv = $argv;
        $this->fd = $fd;
        $this->from_fd = $from_fd;
    }


    //初始化执行函数，支持自定义init
    public function init()
    {
        return true;
    }

    //提前过滤
    protected function preFilter()
    {
        return true;
    }
}
