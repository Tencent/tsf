<?php

/**
 * @Author: winterswang
 * @Date:   2014-11-27 14:58:28
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-07-03 18:01:05
 *
 * @introduction：提供swoole底层的所有能力，提供给http，tcp，udp继承调用
 */
class Controller
{
    protected $server; //启动controller的server实例
    protected $argv = array();
    protected $request;
    protected $fd;
    protected $from_fd;

    /**
     * 为了兼容，fd可以不传
     * @param [type] $pbData [description]
     */

    function __construct($server, $argv = array(), $fd = 0, $from_fd = 0)
    {


        $this->server = $server;
        $this->argv = $argv;
        $this->fd = $fd;
        $this->from_fd = $from_fd;
    }


    //初始化执行函数，支持自定义init
    public function init()
    {
        return true;
    }

    //提前过滤
    protected function preFilter()
    {
        return true;
    }
}
