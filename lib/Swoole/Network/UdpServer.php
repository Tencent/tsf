<?php
namespace Swoole\Network;
/**
 * Class Server
 * @package Swoole\Network
 */
class UdpServer extends \Swoole\Servers
{
    protected $sockType = SWOOLE_SOCK_UDP;

    public $setting = array( // udp server 默认配置

    );
}
