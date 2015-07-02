<?php

namespace Swoole;

abstract class Server implements Server\Driver
{
    protected $sw;
    protected $processName = 'swooleServ';
    protected $host = '0.0.0.0';
    protected $port = 8088;
    protected $listen;
    protected $mode = SWOOLE_PROCESS;
    protected $sockType;
    protected $udpListener;
    protected $tcpListener;
    public $config = array(); //修改为public ---mark  20150620
    protected $setting = array();
    protected $runPath = '/tmp';
    protected $masterPidFile;
    protected $managerPidFile;
    protected $user;
    protected $enableHttp = false;
    //private $protocol;


    private $preSysCmd = '%+-swoole%+-';
    private $requireFile = '';

   // public $serverClass;  //修改为public ---mark  20150620
    public $protocol;  //修改为public ---mark  20150620

	function __construct()
	{
        // Initialization server startup parameters
        $this->setting = array_merge(array(
            'worker_num' => 8,                      // worker process num
            'backlog' => 128,                       // listen backlog
            'log_file' => '/tmp/swoole.log',      // server log
        ), $this->setting);

        $this->setHost();
        $this->init();
	}

    public function init() {

    }

    public function setRequire($file)
    {
        if (! file_exists($file))
        {
            throw new \Exception("[error] require file :$file is not exists");
        }
        $this->requireFile = $file;
    }

    public function setProcessName($processName)
    {

        $this->processName = $processName;
    }


    public function loadConfig($config = array())
    {
        if (is_string($config))
        {   // $config is file path?
            if (! file_exists($config))
            {
                throw new \Exception("[error] profiles [$config] can not be loaded");
            }
            // Load the configuration file into an array
            $config = parse_ini_file($config, true);
        }
        if (is_array($config))
        {
            $this->config = array_merge($this->config, $config);
        }
        return true;
    }

    protected function _initRunTime()
    {
        $mainSetting = $this->config['server'] ? $this->config['server'] : array();
        $runSetting = $this->config['setting'] ? $this->config['setting'] : array();
        //$this->processName = $mainSetting['server_name'] ? $mainSetting['server_name'] : 'swoole_server'; //todo
        $this->masterPidFile =  $this->runPath . '/' . $this->processName . '.master.pid';
        $this->managerPidFile = $this->runPath . '/' . $this->processName . '.manager.pid';
        $this->setting = array_merge($this->setting, $runSetting);
   //     $this->serverClass = $mainSetting['server_name'] ? $mainSetting['server_name'] : 'swoole_server'; //todo

        // trans listener
        if ($mainSetting['listen'])
        {
            $this->transListener($mainSetting['listen']);
        }

        // set user
        if (isset($mainSetting['user']))
        {
            $this->user = $mainSetting['user'];
        }

        if ($this->listen[0]) {
            $this->host = $this->listen[0]['host'] ? $this->listen[0]['host'] : $this->host;
            $this->port = $this->listen[0]['port'] ? $this->listen[0]['port'] : $this->port;
            unset($this->listen[0]);
        }
    }

    private function initServer() {
        // Creating a swoole server resource object
        $swooleServerName = $this->enableHttp ? '\swoole_http_server' : '\swoole_server';
        $this->sw = new $swooleServerName($this->host, $this->port, $this->mode, $this->sockType);


        //一个临时的兼容
        $this->setting['worker_num'] = intval($this->setting['worker_num']);
        $this->setting['dispatch_mode'] = intval($this->setting['dispatch_mode']);
        $this->setting['daemonize'] = intval($this->setting['daemonize']);

       // $this->sw = new \swoole_http_server($this->host, $this->port, $this->mode, $this->sockType);
        // Setting the runtime parameters
        $this->sw->set($this->setting);

        // Set Event Server callback function
        $this->sw->on('Start', array($this, 'onMasterStart'));
        $this->sw->on('ManagerStart', array($this, 'onManagerStart'));
        $this->sw->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->sw->on('Connect', array($this, 'onConnect'));
        $this->sw->on('Receive', array($this, 'onReceive'));
        $this->sw->on('Close', array($this, 'onClose'));
        $this->sw->on('WorkerStop', array($this, 'onWorkerStop'));
        $this->sw->on('timer',array($this, 'onTimer'));
        if ($this->enableHttp) {
            $this->sw->on('Request', array($this, 'onRequest'));
        }
        if (isset($this->setting['task_worker_num'])) {
            $this->sw->on('Task', array($this, 'onTask'));
            $this->sw->on('Finish', array($this, 'onFinish'));
        }

        // add listener
        if (is_array($this->listen))
        {
            foreach($this->listen as $v)
            {
                if (! $v['host'] || ! $v['port'])
                {
                    continue;
                }
                $this->sw->addlistener($v['host'], $v['port'], $this->sockType);
            }
        }
    }

    private function transListener($listen)
    {
        if(! is_array($listen))
        {
            $tmpArr = explode(":", $listen);
            $host = isset($tmpArr[1]) ? $tmpArr[0] : $this->host;
            $port = isset($tmpArr[1]) ? $tmpArr[1] : $tmpArr[0];

            $this->listen[] = array(
                'host' => $host,
                'port' => $port,
            );
            return true;
        }
        foreach($listen as $v)
        {
            $this->transListener($v);
        }
    }

    public function onMasterStart($server)
    {
        Console::setProcessName($this->processName . ': master process');
        file_put_contents($this->masterPidFile, $server->master_pid);
        file_put_contents($this->managerPidFile, $server->manager_pid);
        if ($this->user)
        {
            Console::changeUser($this->user);
        }
    }

    public function onManagerStart($server)
    {
        // rename manager process
        Console::setProcessName($this->processName . ': manager process');
        if ($this->user)
        {
            Console::changeUser($this->user);
        }
    }

    public function onWorkerStart($server, $workerId)
    {
        // echo __METHOD__;
        // exit();
        if($workerId >= $this->setting['worker_num'])
        {
            Console::setProcessName($this->processName  . ': task worker process');
        }
        else
        {
            Console::setProcessName($this->processName  . ': event worker process');
        }

        if ($this->user)
        {
            Console::changeUser($this->user);
        }
        $protocol=(require_once $this->requireFile);//执行

        $this->setProtocol($protocol);
        // check protocol class
        if (! $this->protocol)
        {
            throw new \Exception("[error] the protocol class  is empty or undefined");
        }

        $this->protocol->onStart($server, $workerId);
    }

    public function onConnect($server, $fd, $fromId)
    {
        // $this->log("Client connected : fd=$fd|fromId=$fromId");
        $this->protocol->onConnect($server, $fd, $fromId);
    }

    public function onTask($server, $taskId, $fromId, $data)
    {
        $this->protocol->onTask($server, $taskId, $fromId, $data);
    }

    public function onFinish($server, $taskId, $data)
    {
        $this->protocol->onFinish($server, $taskId, $data);
    }

    public function onClose($server, $fd, $fromId)
    {
        $this->protocol->onClose($server, $fd, $fromId);
    }

    public function onWorkerStop($server, $workerId)
    {
        $this->protocol->onShutdown($server, $workerId);
    }

    public function onTimer($server, $interval)
    {
        $this->protocol->onTimer($server, $interval);
    }

    public function onRequest($request, $response) {
   /*
            设定一个全局的协程调度对象
         */
        if (method_exists($this ->protocol, 'onHttpWorkInit')) {
            $this ->protocol ->onHttpWorkInit($request, $response);
        }
        $this->protocol->onRequest($request, $response);
    }

    public function onReceive($server, $fd, $fromId, $data)
    {
        if($data ==  $this->preSysCmd . "reload")
        {
            $ret = intval($server->reload());
            $server->send($fd, $ret);
        }
        elseif($data ==  $this->preSysCmd . "info")
        {
            $info = $server->connection_info($fd);
            $server->send($fd, 'Info: '.var_export($info, true).PHP_EOL);
        }
        elseif($data ==  $this->preSysCmd . "stats")
        {
            $serv_stats = $server->stats();
            $server->send($fd, 'Stats: '.var_export($serv_stats, true).PHP_EOL);
        }
        elseif($data ==  $this->preSysCmd . "shutdown")
        {
            $server->shutdown();
        }
        else
       {
           $this->protocol->onReceive($server, $fd, $fromId, $data);
       }
    }

	public function setProtocol($protocol)
	{
        if(!($protocol instanceof \Swoole\Server\Protocol))
        {
             throw new \Exception("[error] The protocol is not instanceof \\Swoole\\Server\\Protocol");
        }
//        self::$protocol=$protocol;
  //      self::$protocol->server=$protocol;
		$this->protocol = $protocol;
        $this->protocol->server = $this->sw;
	}

    public function run($setting = array()) {
        echo __METHOD__.PHP_EOL;
        $this->setting = array_merge($this->setting, $setting);
        $cmd = isset($_SERVER['argv'][1]) ? strtolower($_SERVER['argv'][1]) : 'help';
        $this->_initRunTime(); // 初始化server资源
        switch ($cmd) {
            //stop
            case 'stop':
                $this->shutdown();
                break;
            //start
            case 'start':
                $this->initServer();
                $this->start();
                break;
            //reload worker
            case 'reload':
                $this->reload();
                break;
            case 'restart':
                $this->shutdown();
                sleep(2);
                $this->initServer();
                $this->start();
                break;
            case 'status':
                $this->status();
                break;
            default:
                echo 'Usage:php swoole.php start | stop | reload | restart | status | help' . PHP_EOL;
                break;
        }
    }


    protected function start()
    {
        // if ($this->checkServerIsRunning()) {
        //    $this->log("[warning] " . $this->processName . ": master process file " . $this->masterPidFile . " has already exists!");
        //    $this->log($this->processName . ": start\033[31;40m [OK] \033[0m");
        //    return false;
        // }
        $this->log($this->processName . ": start\033[31;40m [OK] \033[0m");
        $this->sw->start();
    }


    protected function shutdown()
    {
        $masterId = $this->getMasterPid();
        if (! $masterId) {
            $this->log("[warning] " . $this->processName . ": can not find master pid file");
            $this->log($this->processName . ": stop\033[31;40m [FAIL] \033[0m");
            return false;
        }
        elseif (! posix_kill($masterId, 15))
        {
            $this->log("[warning] " . $this->processName . ": send signal to master failed");
            $this->log($this->processName . ": stop\033[31;40m [FAIL] \033[0m");
            return false;
        }
        unlink($this->masterPidFile);
        unlink($this->managerPidFile);
        usleep(50000);
        $this->log($this->processName . ": stop\033[31;40m [OK] \033[0m");
        return true;
    }

    protected function reload()
    {
        $managerId = $this->getManagerPid();
        if (! $managerId) {
            $this->log("[warning] " . $this->processName . ": can not find manager pid file");
            $this->log($this->processName . ": reload\033[31;40m [FAIL] \033[0m");
            return false;
        }
        elseif (! posix_kill($managerId, 10))//USR1
        {
            $this->log("[warning] " . $this->processName . ": send signal to manager failed");
            $this->log($this->processName . ": stop\033[31;40m [FAIL] \033[0m");
            return false;
        }
        $this->log($this->processName . ": reload\033[31;40m [OK] \033[0m");
        return true;
    }

    protected function  status()
    {
        $this->log("*****************************************************************");
        $this->log("Summary: ");
        $this->log("Swoole Version: " . SWOOLE_VERSION);
        if (! $this->checkServerIsRunning()) {
            $this->log($this->processName . ": is running \033[31;40m [FAIL] \033[0m");
            $this->log("*****************************************************************");
            return false;
        }
        $this->log($this->processName . ": is running \033[31;40m [OK] \033[0m");
        $this->log("master pid : is " . $this->getMasterPid());
        $this->log("manager pid : is " . $this->getManagerPid());
        $this->log("*****************************************************************");
    }

    protected function getMasterPid() {
        $pid = false;
        if (file_exists($this->masterPidFile)) {
            $pid = file_get_contents($this->masterPidFile);
        }
        return $pid;
    }

    protected function getManagerPid() {
        $pid = false;
        if (file_exists($this->managerPidFile)) {
            $pid = file_get_contents($this->managerPidFile);
        }
        return $pid;
    }

    protected function checkServerIsRunning() {
        $pid = $this->getMasterPid();
        return $pid && $this->checkPidIsRunning($pid);
    }

    protected function checkPidIsRunning($pid) {
        return posix_kill($pid, 0);
    }

    public function close($client_id)
    {
        swoole_server_close($this->sw, $client_id);
    }

    public function send($client_id, $data)
    {
        swoole_server_send($this->sw, $client_id, $data);
    }

    public function daemonize()
    {
        $this->setting['setting']['daemonize'] = 1;
    }

    protected function setHost() {
        $ipList = swoole_get_local_ip();
        if (isset($ipList['eth1']))
        {
            $this->host = $ipList['eth1'];
        }
        elseif(isset($ipList['eth0'])) {
            $this->host = $ipList['eth0'];
        }
        else
        {
            $this->host = '0.0.0.0';
        }
    }

    public function log($msg)
    {
        if ($this->sw->setting['log_file'] && file_exists($this->sw->setting['log_file']))
        {
            error_log($msg . PHP_EOL, 3, $this->sw->setting['log_file']);
        }
        echo $msg . PHP_EOL;
    }

}



