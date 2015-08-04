<?php
/**
 * @Author: winterswang
 * @Date:   2015-06-27 15:50:26
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-07-01 16:25:47
 */

// 增加命名空间
namespace Swoole\Client;

//require_once "Base.php";

class Protocal extends Base
{

    public $host;
    public $port;
    public $data;
    public $timeout;
    public $protocolType;

    public function __construct($host, $port, $data, $timeout, $protocolType)
    {

    }

    /**
     * [sendData 发包，子类继承]
     * @param  callable $callback [description]
     * @return [type]             [description]
     */
    public function send(callable $callback)
    {

        /*
            1.发包协议可以是UDP/TCP/HTTP
            2.本函数负责发包
            3.回调到本地函数，解包，再回调给协程调度器
         */
    }

    /**
     * [unPackRsp 解包]
     * @return [type] [description]
     */
    public function unPackRsp($r, $k, $calltime, $data)
    {

    }

    /**
     * [createClient description]
     * @return [type] [description]
     */
    public function createClient($ip, $port, $data, $timeout, $protocolType)
    {
        switch ($protocolType) {
            case 'udp':
                $client = new UDP($ip, $port, $data, $timeout);
                break;
            case 'tcp':
                $client = new TCP($ip, $port, $data, $timeout);
                break;
            default:
                \SysLog::error(__METHOD__ . " protocolType valid ==> $protocolType", __CLASS__);
                return false;
                break;
        }

        return $client;
    }
}