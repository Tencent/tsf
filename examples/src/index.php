<?php
/**
 * @Author: winterswang
 * @Date:   2015-07-03 14:41:59
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-07-03 16:14:48
 */

//所有的请求都到index  之后通过psf 来启动对应的server

$tsf='/data/web_deployment/winters/tsf/lib/tsf/tsf.php'; //加载psf的内容

//require_once dirname((dirname(__FILE__))).'/lib/psf/auto_load.php'; //加载psf autoload组

//TestAutoLoad::addRoot(PSFBASEPATH);

$config=dirname(__FILE__).'/config/UserConfig.php'; //加载用户的config 一些非标准化的配置文件，需要在这边加载 其实可以不需要只是代入路径

//业务的config


require_once($tsf);


//执行xxx方法----prerequest

//之后再去run （run 进行路由解析）


return Tii::createHttpApplication($config); //返回