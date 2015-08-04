<?php
/**
 * @Author: winterswang
 * @Date:   2015-06-25 16:01:02
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-06-27 17:16:10
 */
// 增加命名空间
namespace Swoole\Client;

class Base
{

    public $ip;
    public $port;
    public $data;
    public $timeout = 5;

    public function __construct($ip, $port, $data, $timeout)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->data = $data;
        $this->timeout = $timeout;
    }

    public function send(callable $callback)
    {


    }
}