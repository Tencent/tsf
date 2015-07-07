<?php
/**
 * Created by PhpStorm, defined by wallyzhang.
 * User: markyuan
 * Date: 2015/6/19
 * Time: 21:06
 * Version: 1.0
 */


//作为一个守护进程？   可以查看启动哪些server

define('STARTBASEPATH', dirname(dirname(__FILE__)));
define('SuperProcessName','Swoole-Controller');
define('uniSockPath',"/tmp/".SuperProcessName.".sock");

$cmds=array('start','stop','reload','restart','shutdown','status');

//php swoole.php testserver start


$name = $argv[1];
$cmd = $argv[2];   //cmd name
$cmd=empty($cmd)?$name:$cmd;
$RunningServer=array();
//需要cmd 和 name  name 支持 all 和 具体的serverName
if ( !$cmd || (!$name && ($cmd!='status' && $cmd!='shutdown') ) || !in_array($cmd,$cmds) )
{
    printInfo();
}

//todo 支持 list
if(($cmd!='status' && $cmd!='shutdown')){
    //获取所有的server名称
    $configDir = STARTBASEPATH . "/conf/*.ini";
    $configArr = glob($configDir);
// 配置名必须是servername
    $servArr = array();
    foreach($configArr as $k => $v)
    {
        $servArr[] = basename($v, '.ini');//获取所有的neame
    }
    //合法性校验   支持自杀 单独一个命令字
    if ((! in_array($name, $servArr))) {
        echo "your server name  $name not exist".PHP_EOL;
        exit;
    }
}


if(CheckProcessExist() ){ //如果存在 说明已经运行了 则通过unixsock通信

    //如果要自杀 先杀掉所有的 然后再自杀吧
    if($cmd=='shutdown'){
        $ret=sendCmdToServ(array('cmd'=>'shutdown','server'=>$name));
        StartLog(__LINE__.' sendCmdToServ ret is'.print_r($ret,true));
        //获取status 之后去杀掉进程
        if($ret['r']==0) {
            //先杀掉所有的run server
            foreach($ret['data'] as $server ){
                // array('php'=>,'name'=)
                $ret=system("ps aux | grep ".$server['name']." | grep master | grep -v grep ");
                preg_match('/\d+/', $ret, $match);//匹配出来进程号
                $ServerId=$match['0'];
                if(posix_kill($ServerId, 15)){//如果成功了
                    echo ' stop '.$server['name'].' success '.PHP_EOL;
                }else{
                    echo ' stop '.$server['name'].' failed '.PHP_EOL;
                }
            };
            //然后开始杀Swoole-Controller
            $ret=system("  ps aux | grep ".SuperProcessName." | grep -v grep");
            preg_match('/\d+/', $ret, $match);
            $ServerId=$match['0'];
            if(posix_kill($ServerId, 15)){//如果成功了
                echo ' stop '.SuperProcessName.' success '.PHP_EOL;
            }else{
                echo ' stop '.SuperProcessName.' failed '.PHP_EOL;
            }
        }else{
            echo 'cmd is '.$cmd.PHP_EOL.' and return is '.print_r($ret,true).PHP_EOL;
        }
        exit;
    }else{
        //命令发给服务
        $ret=sendCmdToServ(array('cmd'=>$cmd,'server'=>$name));
        if($ret['r']==0){
            //临时的status优化
            if($cmd=='status'){
                if(empty($ret['data'])){
                    echo 'cmd is '.$cmd.PHP_EOL.' and return is '.print_r($ret,true).PHP_EOL;
                }else{
                    echo SuperProcessName.' is running '.PHP_EOL;
                    foreach($ret['data'] as $single){
                        echo 'Server Name is '.$single['name'].'    '.'and php start path is '.$single['php'].PHP_EOL;
                    }
                }


            }else{
                echo 'cmd is '.$cmd.PHP_EOL.' and return is '.print_r($ret,true).PHP_EOL;

            }
        }else{
            echo 'cmd is '.$cmd.PHP_EOL.' and return is '.print_r($ret,true).PHP_EOL;
        }
    }
    exit;
}else{  //第一次启动，则启动server 并且添加监控进程
    //提前读取配置 获取php启动路径 目前只支持一个

    if($cmd=='shutdown' || $cmd=='status' || $cmd=='list' ){
        echo __LINE__.'   '.SuperProcessName. ' is not running,please check it'.PHP_EOL;
        exit;
    }
    $indexConf=getServerIni($name);
    if($indexConf['r']!=0){ //
        echo "get server $name conf error".PHP_EOL;
        exit;
    };
    $phpStart=$indexConf['conf']['server']['php'];
    if(empty($phpStart)){
        echo " $name phpstartpath $phpStart not exist ".PHP_EOL;
        exit;
    };
    if ($cmd == 'start')
    {
        //先处理单个 注意异常处理的情况
        $process = new swoole_process(function(swoole_process $worker) use($name,$cmd,$phpStart){//目前指支持一个
            $worker->exec($phpStart, array( STARTBASEPATH . "/lib/Swoole/shell/start.php",$cmd,$name));//拉起server
        }, false);
        $pid = $process->start();
        $exeRet=swoole_process::wait();
        if($exeRet['code']){//创建失败
            echo $phpStart.' '.$name.' '.$cmd.' error '.PHP_EOL;
            return;
        }
        //创建成功 进入daemon模式，开启unix sock
        echo $phpStart.' '.$name.' '.$cmd.' success '.PHP_EOL;
        swoole_process::daemon();
        //开启unixsock 监听模式
        //$RunningServer[$name]=$name;
        //修改，添加参数 包括php启动路径和名字

        $RunningServer[$name]=array('php'=>$phpStart,'name'=>$name);
        error_log(PHP_EOL.__LINE__.PHP_EOL,3,'/tmp/SuperMaster.log');

        StartServSock($RunningServer);

    }

}



function StartServSock($RunServer)
{
    cli_set_process_title(SuperProcessName);
    //这边其实也是也是demon进程
    $serv = new swoole_server(uniSockPath, 0, SWOOLE_BASE, SWOOLE_UNIX_STREAM);
    //维持一个动态数组 实现动态监控server 包含了php的启动路径和停止路径 array('php'=>,'name'=)
    $serv->runServer=$RunServer;
    $serv->set(array(
        'worker_num' => 1,
        'daemonize'=> true
    ));
    $serv->on('WorkerStart', function ($serv, $workerId) {
        //监控周期
        $serv->addtimer(1000);

    });
    error_log(PHP_EOL.__LINE__.PHP_EOL,3,'/tmp/SuperMaster.log');
    //定时器中操作 主要为轮巡 启动服务
    $serv->on('Timer', function ($serv, $interval)  {
        StartLogTimer(__LINE__.'timer start '.time());
        if(empty($serv->runServer )){
            StartLogTimer(__LINE__.' '.'no server is running '.PHP_EOL);
            return;
        };
        foreach($serv->runServer as $serverName){
            $ret=system("ps aux | grep ".$serverName['name']." | grep master | grep -v grep ");
            StartLogTimer(__LINE__.' cmd is '."ps aux | grep ".$serverName['name']." | grep master | grep -v grep ".print_r($ret,true));
            if(empty($ret)){//挂了 什么都没有  之后可能要通过数量来获取
                //todo
                StartServ($serverName['php'],'start',$serverName['name']);
                StartLogTimer(__LINE__.date('Y-m-d H:i:s').'  '.print_r($serverName,true).' server is dead , start to restart');

            }else{
                StartLogTimer(__LINE__.date('Y-m-d H:i:s').'  '.print_r($serverName,true).' server is running success');
            }
        }
        StartLogTimer(__LINE__.date('Y-m-d H:i:s').'  '.print_r($serv->runServer,true).' server is dead , start to restart');

    });
    error_log(PHP_EOL.__LINE__.PHP_EOL,3,'/tmp/SuperMaster.log');


    $serv->on('connect', function ($serv, $fd, $from_id) {
        echo "[#" . posix_getpid() . "]\tClient@[$fd:$from_id]: Connect.\n";
    });

    error_log(PHP_EOL.__LINE__.PHP_EOL,3,'/tmp/SuperMaster.log');

    $serv->on('receive', function ( $serv, $fd, $from_id, $data) {
        StartLog(__LINE__.'receive data is'.print_r($data,true));
        $opData=json_decode($data,true);
        if($opData['cmd']=='start'){ //添加到runserver  还是需要获取路径 存入数组中
            if(isset($serv->runServer[$opData['server']])){ //如果已经有了，说明服务已经启动
                $serv->send($fd, json_encode(array('r'=>1,"msg" => $opData['server'].' is already running')));
                StartLog(__LINE__.'receive data is'.json_encode(array('r'=>1,"msg" => $opData['server'].' is already running')));
                return;
            };
            //如果没有，则读取配置  todo 回调中是否可以执行 getServerIni？  验证了可以
            $retConf=getServerIni($opData['server']);
            if($retConf['r']!=0){ //
                $serv->send($fd, json_encode($retConf));
                return;
            }else{//正常启动
                $phpStart=$retConf['conf']['server']['php'];
                StartServ($phpStart,'start',$opData['server']);
                StartLog(__LINE__." $phpStart ".STARTBASEPATH . "/lib/Swoole/shell/start.php ".$opData['cmd'].' '.$opData['server']);
                $serv->runServer[$opData['server']]=array('php'=>$phpStart,'name'=>$opData['server']); //添加到runServer中
                $serv->send($fd, json_encode(array('r'=>0,'msg' => 'server start success')));
                return;
            }
        }
        elseif($opData['cmd']=='stop'){ //从runserver中干掉
            $phpStart=$serv->runServer[$opData['server']]['php'];//获取php启动路径
            unset($serv->runServer[$opData['server']]);
            StartLog(__LINE__.'THIS RUNSERVER IS'.print_r($serv->runServer,true));

            StartServ($phpStart,'stop',$opData['server']);

            StartLog(__LINE__."$phpStart ".STARTBASEPATH . "/lib/Swoole/shell/start.php ".$opData['cmd'].' '.$opData['server']);

            $serv->send($fd, json_encode(array('r'=>0,'msg' => 'server stop success')));
            return;
        }elseif($opData['cmd']=='status'){ //获取所有服务的状态

            StartLog(__LINE__.json_encode(array('r'=>0,'msg' => 'server running success '.print_r($serv->runServer,true))));
            $serv->send($fd, json_encode(array('r'=>0,'data' =>$serv->runServer)));
            return;
        }elseif($opData['cmd']=='shutdown'){ //获取所有服务的状态

            StartLog(__LINE__.json_encode(array('r'=>0,'msg' => 'server running success '.print_r($serv->runServer,true))));
            $serv->send($fd, json_encode(array('r'=>0,'data' =>$serv->runServer)));
            //清除所有的runServer序列
            unset($serv->runServer);
            return;
        }elseif($opData['cmd']=='reload'){ //重载所有服务
            $phpStart=$serv->runServer[$opData['server']]['php'];//获取php启动路径
            StartLog(__LINE__."$phpStart ".STARTBASEPATH . "/lib/Swoole/shell/start.php ".$opData['cmd'].' '.$opData['server']);
            StartServ($phpStart,'reload',$opData['server']);

            $serv->send($fd, json_encode(array('r'=>0,'msg' => 'server start success')));
            return;
        }elseif($opData['cmd']=='restart'){ //重启所有服务
            $phpStart=$serv->runServer[$opData['server']]['php'];//获取php启动路径
            //首先unset 防止被自动拉起，然后停止，然后sleep 然后start
            unset($serv->runServer[$opData['server']]);//从runserver中干掉

            StartServ($phpStart,'stop',$opData['server']);

            StartLog(__LINE__."$phpStart ".STARTBASEPATH . "/lib/Swoole/shell/start.php ".' stop '.$opData['server']);

            sleep(2);
         //   exec("$phpStart ".STARTBASEPATH . "/lib/Swoole/shell/start.php ".' start '.$opData['server']);//
            StartServ($phpStart,'start',$opData['server']);

            StartLog(__LINE__."$phpStart ".STARTBASEPATH . "/lib/Swoole/shell/start.php ".' start '.$opData['server']);

            $serv->runServer[$opData['server']]=array('php'=>$phpStart,'name'=>$opData['server']); //添加到runServer中
            $serv->send($fd, json_encode(array('r'=>0,'msg' => 'server restart success')));
            return;
        }

    });

    $serv->on('close', function ($serv, $fd, $from_id) {
        echo "[#" . posix_getpid() . "]\tClient@[$fd:$from_id]: Close.\n";
    });

    $serv->start();
    //之后不会再执行任何代码
}


function CheckProcessExist()
{
    $ret=system("ps aux | grep ".SuperProcessName." | grep -v grep ");
    StartLog(__LINE__."ps aux | grep ".SuperProcessName." | grep -v grep  and return ".print_r($ret,true));
    if(empty($ret)) {//挂了 什么都没有  之后可能要通过数量来获取}
        return false;
    }else{
        return true;
    }
}



function getServerIni($serverName)
{
    $configPath=STARTBASEPATH . "/conf/".$serverName.".ini";
    if (! file_exists($configPath))
    {
        return array('r'=>404,'msg'=>'missing config path'.$configPath);
    }
    $config = parse_ini_file($configPath, true);
    return array('r'=>0,'conf'=>$config);
}

function StartLog($msg)
{
    error_log($msg.PHP_EOL,3,'/tmp/SuperMaster.log');
}
function StartLogTimer($msg)
{
    error_log($msg.PHP_EOL,3,'/tmp/SuperMasterTimer.log');
}


function StartServ($phpStart,$cmd,$name){
    $process = new swoole_process(function(swoole_process $worker) use($name,$cmd,$phpStart){//目前指支持一个
        $worker->exec($phpStart, array( STARTBASEPATH . "/lib/Swoole/shell/start.php",$cmd,$name));//拉起server
        StartLogTimer(__LINE__.'   '.$phpStart.' '.STARTBASEPATH. '/lib/Swoole/shell/start.php '.$cmd.' '.$name );

    }, false);
    $pid = $process->start();
    $exeRet=swoole_process::wait();
    return;
}


//用于和守护进程进行通信
function sendCmdToServ($data){
    $client = new swoole_client(SWOOLE_UNIX_STREAM, SWOOLE_SOCK_SYNC);
    $client->connect(uniSockPath, 0);
    $client->send(json_encode($data));
    $ret=$client->recv();
    StartLog(__LINE__.print_r($ret,true));
    $ret=json_decode($ret,true);
    $client->close();
    return $ret;
}

//用于和守护进程进行通信
function printInfo(){
    echo "welcome to use Swoole-Controller,we can help you to monitor your swoole server!".PHP_EOL;
    echo "please input server name and cmd:  php swoole.php myServerName start ".PHP_EOL;
    echo "support cmds: start stop reload restart status ".PHP_EOL;
    echo "if you want to stop Swoole-Controller please input :  php swoole.php shutdown".PHP_EOL;
    echo "if you want to know running servername please input :  php swoole.php list".PHP_EOL;
    echo "if you want to know server list that you can start please input :  php swoole.php list".PHP_EOL;
    exit;
}
