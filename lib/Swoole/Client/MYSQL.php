<?php
/**
 * @Author: wangguangchao
 * @Date:   2015-07-09 18:55:36
 * @Last Modified by:   wangguangchao
 * @Last Modified time: 2015-07-10 10:47:53
 */

namespace Swoole\Client;
//require_once "Base.php";

class MYSQL extends Base
{

    protected $db;
    protected $sql;
    protected $key;
    protected $conf;
    protected $callback;
    protected $calltime;

    /**
     * [__construct 构造函数，初始化mysqli]
     * @param [type] $sqlConf [description]
     */
    public function __construct($sqlConf)
    {

        /*
            sqlConf = array(
                'host' => ,
                'port' => ,
                'user' => ,
                'psw' => ,
                'database' => ,
                'charset' => ,
            );
         */

        $this->db = new \mysqli();
        $this->conf = $sqlConf;
    }


    /**
     * [send 兼容Base类封装的send方法，调度器可以不感知client类型]
     * @param  [type] $callback [description]
     * @return [type]           [description]
     */
    public function send(callable $callback)
    {

        if (!isset($this->db)) {

            echo " db not init \n";
            //TODO do callback function to task
            return;
        }
        //TODO conf check

        $config = $this->conf;
        $this->callback = $callback;
        $this->calltime = microtime(true);
        $this->key = md5($this->calltime . $config['host'] . $config['port'] . rand(0, 10000));

        $this->db->connect($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);

        if (!empty($config['charset'])) {
            $this->db->set_charset($config['charset']);
        }

        $db_sock = swoole_get_mysqli_sock($this->db);
        swoole_event_add($db_sock, array($this, 'onSqlReady'));

        $this->doQuery($this->sql);
    }

    /**
     * [query 使用者调用该接口，返回当前mysql实例]
     * @param  [type] $sql [description]
     * @return [type]      [description]
     */
    public function query($sql)
    {

        $this->sql = $sql;
        yield $this;
    }


    /**
     * [doQuery 异步查询，两次重试]
     * @param  [type] $sql [description]
     * @return [type]      [description]
     */
    public function doQuery($sql)
    {

        // retry twice
        for ($i = 0; $i < 2; $i++) {
            $result = $this->db->query($this->sql, MYSQLI_ASYNC);
            if ($result === false) {
                if ($this->db->errno == 2013 or $this->db->errno == 2006) {
                    $this->db->close();
                    $r = $this->db->connect();
                    if ($r === true) {
                        continue;
                    }
                }
            }
            break;
        }
    }

    /**
     * [onSqlReady eventloog异步回调函数]
     * @return [type] [description]
     */
    public function onSqlReady()
    {

        //关链接
        //$this ->db ->close();

        if ($result = $this->db->reap_async_query()) {
            $this->calltime = $this->calltime - microtime(true);

            call_user_func_array($this->callback, array('r' => 0, 'key' => $this->key, 'calltime' => $this->calltime, 'data' => $result->fetch_all()));
            //关链接
            //$this ->db ->close();
            if (is_object($result)) {
                mysqli_free_result($result);
            }
        } else {
            echo "MySQLi Error: " . mysqli_error($this->db) . "\n";
            //TODO log callback 
        }
    }
}

