<?php
/**
 * Created by PhpStorm.
 * User: yuanyizhi
 * Date: 15/6/20
 * Time: 下午12:25
 */

//实现createwebapplication 加载psf的代码 返回一个建好的类


//todo    autoload psf 和 src里面的所有代码
define('TSFBASEPATH', dirname(dirname(__FILE__)));
require_once 'auto_load.php';
require_once TSFBASEPATH.'/Swoole/require.php';        //添加swoole的代码
TestAutoLoad::addRoot(TSFBASEPATH.'/tsf'); //autoload psf所有的代码




class Tii
{
    //config用来配置各个字段 如 url log 等
    public static function   createHttpApplication($config){
        TestAutoLoad::addRoot(dirname(dirname($config))); //autoload 用户的所有代码
        //初始化log组件
        SysLog::init(UserConfig::getConfig('log'));
        return new TSFHttpServ();
        //进行路由解析等
    }


    public static function   createUdpApplication($config){
        TestAutoLoad::addRoot(dirname(dirname($config)));
        $protocalClass = UserConfig::getConfig('protocalClass');
        if(!class_exists($protocalClass)){
            return false;
        }
        $protocal = new $protocalClass();
        $routeClass = UserConfig::getConfig('routeClass');
        if(!class_exists($routeClass)){
            return false;
        }
        $route = new $routeClass();
        SysLog::init(UserConfig::getConfig('log'));
        $udp = new TSFUdpServ();
        $udp -> setUdp($protocal, $route);
        return $udp;
        //return new YaafUdpServ();
       // return $class;
        //进行路由解析等
    }



    public static function   createTcpApplication(){

      //  return $class;
        //进行路由解析等
    }
    public static function   createWebSocketApplication(){

        //  return $class;
        //进行路由解析等
    }

}
