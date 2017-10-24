<?php
/**
 * Tencent is pleased to support the open source community by making TSF Solution available.
 * Copyright (C) 2017 THL A29 Limited, a Tencent company. All rights reserved.
 * Licensed under the BSD 3-Clause License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * https://opensource.org/licenses/BSD-3-Clause
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 */





//用来配置用户自定义的路由规则 以及一些log级别等
 class  UserConfig {
     public  static  $UserConf=array(
         'log'=>array('log_path'=>'/data/log/',
             'log_level'=>array(
                 'info'=>1,
                 'warning'=>1,
                 'notice'=>1,
                 'error'=>1,
                 'debug'=>1,
             )
         ), //支持log不同级别

         'router'=>array('info'=>1,'error'=>1), //支持自定义route 传递一个函数  1111111？
         'preFilter'=>array('info'=>1,'error'=>1), //fliter之前做的事情 传递一个函数？
         'postFilter'=>array('info'=>1,'error'=>1), //fliter之后做的事情 传递一个函数？
     );

     public static function getConfig($val) {
         return self::$UserConf[$val];
     }

}
?>