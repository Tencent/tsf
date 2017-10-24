<?php
/**
 * Tencent is pleased to support the open source community by making TSF Solution available.
 * Copyright (C) 2017 THL A29 Limited, a Tencent company. All rights reserved.
 * Licensed under the BSD 3-Clause License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * https://opensource.org/licenses/BSD-3-Clause
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 */


return array(
    'Rewrite' => array(

        array(
            'regx' => '^/(<controller>\w+)$',  //默认到index
            'mvc' => 'controller/Index',  //必须匹配
            'verb' => '',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),
        array(
            'regx' => '^/(<controller>\w+)/(<action>\w+)$',
            'mvc' => 'controller/Index',
            'verb' => '',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),

        //特殊
        array(
            'regx' => '^/(<controller>\w+)/(<action>\w+)/(<s_action>\w+)$',
            'mvc' => 'controller/Index',
            'verb' => '',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),

        //添加rest
        array(
            'regx' => '^/(<controller>\w+)$',
            'mvc' => 'Controller/Index',
            'verb' => '',
            'default' => array(),
        ),
        array(
            'regx' => '^/(<controller>\w+)/(<action>\w+)$',
            'mvc' => 'Controller/View',  //必须匹配
            'verb' => '',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),


        array(
            'regx' => '^/rest/(<controller>\w+)$',
            'mvc' => 'Controller/List',
            'verb' => 'GET',
            'default' => array('ggg' => 33333),
        ),
        array(
            'regx' => '^/rest/(<controller>\w+)/(<id>\d+)$',
            'mvc' => 'Controller/View',
            'verb' => 'GET',
            'default' => array(),
        ),
        array(
            'regx' => '^/rest/(<controller>\w+)/(<id>\d+)$',
            'mvc' => 'Controller/Update',
            'verb' => 'PUT',
            'default' => array(),
        ),
        array(
            'regx' => '^/rest/(<controller>\w+)$',
            'mvc' => 'Controller/View',
            'verb' => 'GET',
            'default' => array(),
        ),
        array(
            'regx' => '^/rest/(<controller>\w+)/(<id>\d+)$',
            'mvc' => 'Controller/Update',  //必须匹配
            'verb' => 'PUT',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),
        array(
            'regx' => '^/rest/(<controller>\w+)$',
            'mvc' => 'Controller/Create',  //必须匹配
            'verb' => 'POST',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),
        array(
            'regx' => '^/rest/(<controller>\w+)/(<id>\d+)$',
            'mvc' => 'Controller/Delete',  //必须匹配
            'verb' => 'DELETE',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),
        array(
            'regx' => '^/rest/(<controller>\w+)$',
            'mvc' => 'Controller/Delete',  //必须匹配
            'verb' => 'DELETE',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),
        //默认的话就是/controller/action?id=32131 直接定位过去  必须要有
        array(
            /**
             * 默认的==》controller/action
             */
            'regx' => '^/(<controller>\w+)/(<action>\w+)/(<cid>\d+)/(<name>\w+)$',
            'mvc' => 'Controller/Action',  //必须匹配
            'verb' => 'GET',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),
    )


);