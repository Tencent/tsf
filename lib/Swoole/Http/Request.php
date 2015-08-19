<?php
namespace Swoole\Http;

use Swoole;

class Request
{
    /**
     * 文件描述符
     * @var int
     */
    public $fd;
    public $id;

    /**
     * 请求时间
     * @var int
     */
    public $time;

    public $remote_ip;

    public $get = array();
    public $post = array();
    public $file = array();
    public $cookie = array();
    public $session = array();
    public $server = array();

    /**
     * @var \StdClass
     */
    public $attrs;

    public $head = array();
    public $body;
    public $meta = array();

    public $finish = false;
    public $ext_name;
    public $status;

    /**
     * 将原始请求信息转换到PHP超全局变量中
     */
    function setGlobal()
    {
        if ($this->get) $_GET = $this->get;
        if ($this->post) $_POST = $this->post;
        if ($this->file) $_FILES = $this->file;
        if ($this->cookie) $_COOKIE = $this->cookie;
        if ($this->server) $_SERVER = $this->server;
        $_REQUEST = array_merge($this->get, $this->post, $this->cookie);

        $_SERVER['REQUEST_URI'] = $this->meta['uri'];
        /**
         * 将HTTP头信息赋值给$_SERVER超全局变量
         */
        foreach ($this->head as $key => $value) {
            $_key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $_SERVER[$_key] = $value;
        }
        $_SERVER['REMOTE_ADDR'] = $this->remote_ip;
    }

    function unsetGlobal()
    {
        $_REQUEST = $_SESSION = $_COOKIE = $_FILES = $_POST = $_SERVER = $_GET = array();
    }
}