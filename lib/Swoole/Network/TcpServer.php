<?php
namespace Swoole\Network;
/**
 * Class Server
 * @package Swoole\Network
 */
class TcpServer extends \Swoole\Server implements \Swoole\Server\Driver
{
    protected $sockType = SWOOLE_SOCK_TCP;

    public $setting = array(
        //      'open_cpu_affinity' => 1,
        'open_tcp_nodelay' => 1,
    );
}
