<?php
/**
 * Created by JetBrains PhpStorm.
 * User: chalesi
 * Date: 14-2-24
 * Time: 上午11:21
 * To change this template use File | Settings | File Templates.
 */
namespace Swoole\Network\Protocol;

use Swoole;

class ProtobufServer extends Swoole\Network\Protocol implements Swoole\Server\Protocol
{
    private $requests;
    public $webStx;
    public $webEtx;

    const ST_FINISH = 1; //完成，进入处理流程
    const ST_WAIT = 2; //等待数据
    const ST_ERROR = 3; //错误，丢弃此包

    public function onReceive($serv, $fd, $fromId, $data)
    {
        $ret = $this->checkBuffer($fd, $data);// 检查buffer

        switch ($ret) {
            case self::ST_ERROR:
                return true;           // 错误的请求
            case self::ST_WAIT:
                return true;          // 数据不完整，继续等待
            default:
                break;                 // 完整数据
        }
        $request = $this->requests[$fd];
        $this->onRequest($serv, $fd, $request);
        unset($this->requests[$fd]);
    }

    public function checkBuffer($fd, $data)
    {
        //新的连接
        if (!isset($this->requests[$fd])) {
            $webStx = substr($data, 0, 1); // 获取起始符
            if (pack("C", $this->webStx) !== $webStx) {
                return self::ST_ERROR; // 错误的开始符
            }

            // buffer解析
            $cmd = substr($data, 1, 4); // 获取命令号
            $cmdArr = unpack('Ncmd', $cmd);
            $cmd = $cmdArr['cmd'];
            $seq = substr($data, 5, 4);
            $seq = unpack('Nseq', $seq);
            $seq = $seq['seq'];
            $headLen = substr($data, 9, 4);
            $headLen = unpack('Nlen', $headLen);
            $headLen = $headLen['len'];
            $bodyLen = substr($data, 13, 4);
            $bodyLen = unpack('Nlen', $bodyLen);
            $bodyLen = $bodyLen['len'];

            $totalLength = 18 + $headLen + $bodyLen;
            if (strlen($data) > $totalLength) {
                return self::ST_ERROR; // 无效数据包，弃之
            }

            $this->requests[$fd] = array(
                'cmd' => $cmd,
                'seq' => $seq,
                'headLen' => $headLen,
                'bodyLen' => $bodyLen,
                'length' => $totalLength,
                'buffer' => $data,
            );
        } else {  // 大包数据需要合并数据，默认超过8k需要走此逻辑
            $this->requests[$fd]['buffer'] .= $data;
        }

        // 检查包的大小
        $dataLength = strlen($this->requests[$fd]['buffer']);
        if ($dataLength > $this->requests[$fd]['length']) {
            // 无效数据包，弃之
            return self::ST_ERROR;
        } elseif ($dataLength < $this->requests[$fd]['length']) {
            return self::ST_WAIT; // 数据包不完整，继续等待
        }

        $webEtx = substr($data, -1);   // 获取结束符
        if (pack("C", $this->webEtx) !== $webEtx) {
            return self::ST_ERROR;
        }

        return self::ST_FINISH; // 数据包完整
    }

    public function onRequest($serv, $fd, $request)
    {

    }

    public function onStart($serv, $workerId)
    {

    }

    public function onShutdown($serv, $workerId)
    {
    }

    public function onConnect($server, $fd, $fromId)
    {
    }

    public function onClose($server, $fd, $fromId)
    {
        unset($this->requests[$fd]);
    }

    public function onTask($serv, $taskId, $fromId, $data)
    {

    }

    public function onTimer($serv, $interval)
    {

    }

    public function onFinish($serv, $taskId, $data)
    {

    }
}