<?php
/**
 * Created by PhpStorm.
 * User: yuanyizhi
 * Date: 15/6/19
 * Time: 下午11:57
 */
#!/usr/local/php/bin/php -q


//读取配置，启动对应的server 根据传进来的server名字 已经知道协议类型


// 定义根目录
define('SWOOLEBASEPATH', dirname(dirname(__FILE__)));
// 载入Swoole 框架
require_once SWOOLEBASEPATH . '/require.php';
//读取配置
$cmd = $argv[1];   //cmd name
$name = $argv[2];

//需要cmd 和 name  name 支持 all 和 具体的serverName
if (! $cmd || ! $name)
{
    echo "please input cmd and server name: start all,start testserv ";
    exit;
}
//读取配置文件 然后启动对应的server

$configPath=(dirname(dirname(SWOOLEBASEPATH))). '/conf/'.$name.'.ini';//获取配置地址
// $config is file path? 提前读取 只读一次
if (! file_exists($configPath))
{
            throw new \Exception("[error] profiles [$configPath] can not be loaded");
}
        // Load the configuration file into an array
$config = parse_ini_file($configPath, true);
//根据config里面的不同内容启动不同的server  定义网络层 UDP、TCP
if($config['server']['type']=='http'){  //
    $server = new \Swoole\Network\HttpServer();

}elseif($config['server']['type']=='tcp'){
    $server = new \Swoole\Network\TcpServer();

}elseif($config['server']['type']=='udp'){
    $server = new  \Swoole\Network\UdpServer();
}

//合并config 只读一次
$server->config=array_merge($server->config, $config);

echo __LINE__.'xxxxxx'.print_r($server->config,true).PHP_EOL;
//通过root 来获取所有的源码

//获取index一次 加载tsf框架

//获取配置 载入index
//index require



//$server->serverClass='testHttpServ';
$server->setProcessName($name);
//$server->setProcessName('mark_server');

//root为index的路径
$server->setRequire($config['server']['root']);


//源码加载器
//$server->setRequire(BASEPATH . '/src/require.php');

// 启动 此时已经读到了root

$server->run();