<?php
/**
 * @Author: winterswang
 * @Date:   2015-07-03 14:41:59
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-07-06 19:29:44
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
