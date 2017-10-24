<?php
/**
 * Tencent is pleased to support the open source community by making TSF Solution available.
 * Copyright (C) 2017 THL A29 Limited, a Tencent company. All rights reserved.
 * Licensed under the BSD 3-Clause License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * https://opensource.org/licenses/BSD-3-Clause
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 */


//todo    autoload tsf 和 src里面的所有代码
define('TSFBASEPATH', dirname(dirname(__FILE__)));
require_once 'AutoLoad.php';
require_once TSFBASEPATH . '/Swoole/require.php';        //添加swoole的代码
AutoLoad::addRoot(TSFBASEPATH . '/tsf'); //autoload tsf所有的代码


class Tii
{
    //config用来配置各个字段 如 url log 等
    public static function   createHttpApplication($config)
    {
        AutoLoad::addRoot(dirname(dirname($config))); //autoload 用户的所有代码
        //初始化log组件
        SysLog::init(UserConfig::getConfig('log'));
        return new TSFHttpServ();
        //进行路由解析等
    }


    public static function   createUdpApplication($config)
    {
        AutoLoad::addRoot(dirname(dirname($config)));
        SysLog::init(UserConfig::getConfig('log'));
        return new YaafUdpServ();
        // return $class;
        //进行路由解析等
    }


    public static function   createTcpApplication()
    {

        //  return $class;
        //进行路由解析等
    }

    public static function   createWebSocketApplication()
    {

        //  return $class;
        //进行路由解析等
    }

}
