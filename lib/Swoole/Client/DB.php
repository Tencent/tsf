<?php

namespace Swoole\Client;

/**
 * Class MySQL
 * @package Swoole\Async
 */
class MySQL extends Base{
    private $pool;
    
    public function __construct($sql){
    	$this->pool = Db_pool::getInstance();
    }
    
    /**
     * @desc set key
     * @param string $key
     * @return
     */
    public function setKey($key=NULL){
    	if(empty($key)){
    		$this ->key = md5($this->url . microtime(true) . rand(0,10000));
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

        if ($result = $mysqli->reap_async_query()) {
            call_user_func($callback, $mysqli, $result);
            if (is_object($result)) {
                mysqli_free_result($result);
            }
        } else {
            call_user_func($callback, $mysqli, $result);
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
                $this->doQuery($new_task['sql'], $new_task['callback']);
            }
        }
    }
    
    public function send(callable $callback){
    	$this->doQuery($callback);
    }

    /**
     * @param string $sql
     * @param callable $callback
     */
    public function query($sql, callable $callback)
    {
        //no idle connection
        if (count($this->idle_pool) == 0) {
            if ($this->connection_num < $this->pool_size) {
                $this->createConnection();
                $this->doQuery($sql, $callback);
            } else {
                $this->wait_queue[] = array(
                    'sql'  => $sql,
                    'callback' => $callback,
                );
            }
        } else {
            $this->doQuery($sql, $callback);
        }
    }

    /**
     * @param string $sql
     * @param callable $callback
     */
    protected function doQuery($sql, callable $callback)
    {
        //remove from idle pool
        $db = array_pop($this->idle_pool);

        /**
         * @var \mysqli $mysqli
         */
        $mysqli = $db['object'];

        for ($i = 0; $i < 2; $i++) {
            $result = $mysqli->query($sql, MYSQLI_ASYNC);
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
                        'sql'  => $sql,
                        'callback' => $callback,
                    );
                }
            }
            break;
        }

        $task['sql'] = $sql;
        $task['callback'] = $callback;
        $task['mysql'] = $db;

        //join to work pool
        $this->work_pool[$db['socket']] = $task;
    }
}

class Db_pool{
	/**
	 * max connections for mysql client
	 * @var int $pool_size
	 */
	protected $pool_size;
	
	/**
	 * number of current connection
	 * @var int $connection_num
	 */
	protected $connection_num;
	
	/**
	 * idle connection
	 * @var array $idle_pool
	 */
	protected $idle_pool = array();
	
	/**
	 * work connetion
	 * @var array $work_pool
	*/
	protected $work_pool = array();
	
	/**
	 * database configuration
	 * @var array $config
	*/
	protected $config = array();
	
	/**
	 * wait connection
	 * @var array
	*/
	protected $wait_queue = array();
	
	/**
	 * @param array $config
	 * @param int $pool_size
	 * @throws \Exception
	*/
	public static $_instance;
	
	public function __construct(){
		 
		$config = \UserConfig::getConfig("db");
		 
		if (empty($config['host']) ||
			empty($config['database']) ||
			empty($config['user']) ||
			empty($config['password'])
		) {
			throw new \Exception("require host, database, user, password config.");
		}
	
		if (!function_exists('swoole_get_mysqli_sock')) {
			throw new \Exception("require swoole_get_mysqli_sock function.");
		}
	
		if (empty($config['port'])) {
			$config['port'] = 3306;
		}
	
		$this->config = $config;
		$this->pool_size = $config['pool'];
	}
	
	public static function &getInstance(){
		if(!self::$_instance){
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	 * create mysql connection
	 */
	protected function createConnection(callable $callback)
	{
		$config = $this->config;
		$db = new \mysqli;
		$db->connect($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);
		if (!empty($config['charset'])) {
			$db->set_charset($config['charset']);
		}
		$db_sock = swoole_get_mysqli_sock($db);
		swoole_event_add($db_sock, $callback);
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
	protected function removeConnection($db_sock)
	{
		swoole_event_del($db_sock);
		$this->idle_pool[$db_sock]['object'] -> close();
		unset($this->idle_pool[$db_sock]);
		$this->connection_num --;
	}
}