<?php

namespace Swoole\Client;

/**
 * Class DB
 * @package Swoole\Client
 */
class DB extends Base{
    protected $pool;
    protected $db;
    protected $sql;
    protected $key;
    protected $callback;
    protected $calltime;
    
    public function __construct($sql=""){
    	$this->pool = Db_pool::getInstance();
    	$this->sql = $sql;
    }
    
    /**
     * @desc set key
     * @param string $key
     * @return
     */
    public function setKey($key=NULL){
    	if(empty($key)){
    		$this ->key = md5($this->sql . microtime(true) . rand(0,10000));
    	}else{
    		$this ->key = $key;
    	}
    }
    
    /**
     * @desc get key
     * @return string $this->key
     */
    public function getKey(){
    	return $this ->key;
    }


    
    public function send(callable $callback){
    	$this->callback = $callback;
    	$this->ready_query();
    }


    public function query($sql){
        $this->sql = $sql;
        return $this;
    }

    /**
     * @param string $sql
     * @param callable $callback
     */
    public function ready_query()
    {
        //no idle connection
        if(!empty($sql)){
            $this->sql = $sql;
        }

        if (count($this->pool->idle_pool) == 0) {
            if ($this->pool->connection_num < $this->pool->pool_size) {
                $this->pool->createConnection(array($this,"onSQLReady"));
                $this->doQuery();
            } else {
                $this->pool->wait_queue[] = array(
                    'sql'  => $this->sql,
                    'callback' => $this->callback,
                    'calltime'=>$this->calltime,
                    'key'=>$this->key,
                    'object'=>$this,
                );
            }
        } else {
            $this->doQuery();
        }
    }

    /**
     * @param string $sql
     * @param callable $callback
     */
    public function doQuery()
    {


        //remove from idle pool
        $db = array_pop($this->pool->idle_pool);
		
        /**
         * @var \mysqli $mysqli
         */
        $mysqli = $db['object'];
        for ($i = 0; $i < 2; $i++) {
            $result = $mysqli->query($this->sql, MYSQLI_ASYNC);
            if ($result === false) {
                if ($mysqli->errno == 2013 or $mysqli->errno == 2006) {
                    $mysqli->close();
                    $r = $mysqli->connect();
                    if ($r === true) {
                        continue;
                    }
                } else {
                    echo "server exception. \n";
                    $this->connection_num --;
                    $this->wait_queue[] = array(
                        'sql'  => $this->sql,
                        'callback' => $this->callback,
                        'calltime'=>$this->calltime,
                        'key'=>$this->key,
                        'object'=>$this,
                    );
                }
            }
            break;
        }

        $task['sql'] = $this->sql;
        $task['callback'] = $this->callback;
        $task['mysql'] = $db;
        $task['calltime'] = $this->calltime;
        $task['key'] = $this->key;

        //join to work pool
        $this->pool->work_pool[$db['socket']] = $task;

        echo "idle pool:".count($this->pool->idle_pool)."\n";
        echo "work pool:".count($this->pool->work_pool)."\n";
        echo "wait queue:".count($this->pool->wait_queue)."\n";
    }
}

class Db_pool{
	/**
	 * max connections for mysql client
	 * @var int $pool_size
	 */
	public $pool_size;
	
	/**
	 * number of current connection
	 * @var int $connection_num
	 */
	public $connection_num;
	
	/**
	 * idle connection
	 * @var array $idle_pool
	 */
	public $idle_pool = array();
	
	/**
	 * work connetion
	 * @var array $work_pool
	*/
	public $work_pool = array();
	
	/**
	 * database configuration
	 * @var array $config
	*/
	public $config = array();
	
	/**
	 * wait connection
	 * @var array
	*/
	public $wait_queue = array();
	
	/**
	 * @param array $config
	 * @param int $pool_size
	 * @throws \Exception
	*/
	public static $_instance=null;
	
	protected function __construct(){
		 
		$config = \UserConfig::getConfig("db");
		if (empty($config['host']) ||
			empty($config['database']) ||
			empty($config['user']) 
		) {
            echo "require host, database, user, password config";
			//throw new \Exception("require host, database, user, password config.");
		}
		if (!function_exists('swoole_get_mysqli_sock')) {
            echo "require swoole_get_mysqli_sock function.";
			//throw new \Exception("require swoole_get_mysqli_sock function.");
		}
		
		if (empty($config['port'])) {
			$config['port'] = 3306;
		}
	
		$this->config = $config;
		$this->pool_size = $config['pool'];
	}
	
	public static function getInstance(){
		if(self::$_instance===null){
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	 * create mysql connection
	 */
	public function createConnection()
	{
		
		$config = $this->config;
		$db = new \mysqli;
		$db->connect($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);
		if (!empty($config['charset'])) {
			$db->set_charset($config['charset']);
		}
		$db_sock = swoole_get_mysqli_sock($db);
		
		swoole_event_add($db_sock, array($this,"onSQLReady"));
		$this->idle_pool[$db_sock] = array(
			'object' => $db,
			'socket' => $db_sock,
		);
		$this->connection_num ++;
	}
	
	/**
	 * remove mysql connection
	 * @param $db_sock
	 */
	public function removeConnection($db_sock)
	{
		swoole_event_del($db_sock);
		$this->idle_pool[$db_sock]['object'] -> close();
		unset($this->idle_pool[$db_sock]);
		$this->connection_num --;
	}

    /**
     * @param $db_sock
     * @return bool
     */
    public function onSQLReady($db_sock)
    {
        $task = empty($this->work_pool[$db_sock]) ? null : $this->work_pool[$db_sock];
        if (empty($task)) {
            echo "MySQLi Warning: Maybe SQLReady receive a Close event , such as Mysql server close the socket !\n";
            $this->removeConnection($db_sock);
            return false;
        }

        /**
         * @var \mysqli $mysqli
         */
        $mysqli = $task['mysql']['object'];
        $callback = $task['callback'];
        $calltime = $task['calltime'];
        $key  = $task['key'];

        if ($result = $mysqli->reap_async_query()) {
            $calltime = $calltime - microtime(true);
            call_user_func_array($callback, array('r' => 0, 'key' => $key, 'calltime' => $calltime, 'data' => $result ->fetch_all(MYSQLI_ASSOC)));
            if (is_object($result)) {
                mysqli_free_result($result);
            }
        } else {
            $calltime = $calltime - microtime(true);
            call_user_func_array($callback, array('r' => 1, 'key' => $key, 'calltime' => $calltime, 'data' =>"error" ));
            echo "MySQLi Error: " . mysqli_error($mysqli)."\n";
        }


        //release mysqli object
        $this->idle_pool[$task['mysql']['socket']] = $task['mysql'];
        unset($this->work_pool[$db_sock]);


        //fetch a request from wait queue.
        if (count($this->wait_queue) > 0) {
            $idle_n = count($this->idle_pool);
            for ($i = 0; $i < $idle_n; $i++) {
                $new_task = array_shift($this->wait_queue);
                $new_task['object']->doQuery($new_task['callback']);
            }
        }
    }
}