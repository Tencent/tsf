<?php
/**
 * Tencent is pleased to support the open source community by making TSF Solution available.
 * Copyright (C) 2017 THL A29 Limited, a Tencent company. All rights reserved.
 * Licensed under the BSD 3-Clause License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * https://opensource.org/licenses/BSD-3-Clause
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 */

//所有的请求都到index  之后通过psf 来启动对应的server

$tsf = SWOOLEBASEPATH . "/../tsf/tsf.php"; //加载psf的内容



//TestAutoLoad::addRoot(PSFBASEPATH);

$config=dirname(__FILE__).'/config/UserConfig.php'; //加载用户的config 一些非标准化的配置文件，需要在这边加载 其实可以不需要只是代入路径


//加载环境变量    通过 $_SERVER['NEWAPI_ENV']来加载不同的配置文件 require_once dirname(__FILE__) . '/config/envcnf/'. $_SERVER['SERV_ENV'] .'/ENVConst.php';
require_once dirname(__FILE__) . '/config/envcnf/ol/ENVConst.php';

//业务的config

require_once($tsf);

//执行xxx方法----prerequest

//之后再去run （run 进行路由解析）


return Tii::createHttpApplication($config); //返回
