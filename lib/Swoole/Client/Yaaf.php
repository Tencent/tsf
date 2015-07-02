<?php
/**
 * @Author: winterswang
 * @Date:   2015-06-27 15:55:22
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-07-01 16:25:02
 */

// 增加命名空间
namespace Swoole\Client;

//require_once "Protocal.php";

class Yaaf extends Protocal{

	public $key;
	public $host;
	public $port;
	public $data;
    public $client;
    public $timeout;
    public $callback;
   	public $protocolType;

    private $jm;  //json_manege对象,把json打包成二进制buffer
    private $headConf;

    const HEADLEN = 69;
    const SUCCESS = 0;
    const ERROR_UIN = 11;
    const ERROR_CMD = 12;
    const ERROR_PACK_HEAD = 13;
    const ERROR_JSON = 21;
    const ERROR_PACK_BODY = 22;
    const ERROR_PACK_BUF = 31;
    const ERROR_SENDRECV = 41;

	public function __construct($host, $port, $timeout = 5, $headConf = array(), $protocolType = 'udp'){

		$this ->host = $host;
		$this ->port = $port;
		$this ->data = '';
		$this ->key = '';
		$this ->timeout = $timeout;
		$this ->protocolType = $protocolType;
        $this ->jm = new \JsonManager();
        $this ->headConf = array(
            'packFormat' => isset($headConf['packFormat']) ? $headConf['packFormat'] : 'CCCNNNna32nsNNNN',
            'version' => isset($headConf['version']) ? $headConf['version'] : 1,
            'packetType' => isset($headConf['packetType']) ? $headConf['packetType'] : 0,
            'bodyFormat' => isset($headConf['bodyFormat']) ? $headConf['bodyFormat'] : 0,
            'seqNo' => isset($headConf['seqNo']) ? $headConf['seqNo'] : 1,
            'uin' => isset($headConf['uin']) ? $headConf['uin'] : 0,
            'groupId' => isset($headConf['groupId']) ? $headConf['groupId'] : 0,
            'appId' => isset($headConf['appId']) ? $headConf['appId'] : 0,
            'appName' => isset($headConf['appName']) ? $headConf['appName'] : '',
            'cmd' => isset($headConf['cmd']) ? $headConf['cmd'] : 0,
            'defaultErrCode' => isset($headConf['defaultErrCode']) ? $headConf['defaultErrCode'] : 0,
            'clientIp' => isset($headConf['clientIp']) ? $headConf['clientIp'] : 0,
            'serverIp' => isset($headConf['serverIp']) ? $headConf['serverIp'] : 0,
            'flags' => isset($headConf['flags']) ? $headConf['flags'] : 0,
            'reserved' => isset($headConf['reserved']) ? $headConf['reserved'] : 0,
        );
	}

	public function setKey($key){
		$this ->key = $key;
	}

	public function getKey(){
		return $this ->key;
	}

	/**
	 * [request 拼包发请求]
	 * @param  [type] $uin        [description]
	 * @param  [type] $cmd        [description]
	 * @param  [type] $jsonArr    [description]
	 * @return [type]             [description]
	 */
	public function request($uin, $cmd, $jsonArr){

		/*
			1.根据参数，拼接yeaf格式数据
			2.返回当前实例
		 */
		//PACK HEAD
		$this ->data .= $this -> packHead($uin, $cmd);
		//PACK BODY
		$this ->data .= $this -> packBody($jsonArr);
		return $this;
	}

	/**
	 * [sendData 父类封装]
	 * @param  callable $callback [description]
	 * @return [type]             [description]
	 */
	public function send(callable $callback){
		/*
			1.发包协议可以是UDP/TCP/HTTP
			2.本函数负责发包
			3.回调到本地函数，解包，再回调给协程调度器
		 */
		\SysLog::info(__METHOD__. " callback == $callback \n", __CLASS__);
		$this ->callback = $callback;
		$this ->client = $this ->createClient($this ->host, $this ->port, $this ->data, $this ->timeout, $this ->protocolType);
		//SysLog::info(__METHOD__. " client === ".print_r($this ->client, true), __CLASS__);
		$this ->client ->send(array($this, 'unPackRsp'));

	}

	/**
	 * [unPackRsp 反解回包数据]
	 * @param  [type] $r    [description]
	 * @param  [type] $k    [description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */

	public function unPackRsp($r, $k, $calltime, $data){
		/*
			根据yeaf格式解析回包数据
			回调给协程的callback函数，反回数据到调用端
		 */
        \SysLog::info(__METHOD__." udp test client  rsp ==== ".print_r($data,true), __CLASS__);
        if ($r != 0) {
            $result = array('r' => self::ERROR_SENDRECV);
        }
        else{
            $rspHeadBuf = substr($data, 0, self::HEADLEN);
            //echo "rspHeadBuf === $rspHeadBuf \n";
            $rspBodyBuf = substr($data, self::HEADLEN);
            //echo "rspBodyBuf === $rspBodyBuf \n";
            $result = $this ->getResult($rspHeadBuf, $rspBodyBuf);
            //echo " unPackRsp result == ". print_r($result,true) . PHP_EOL;
         }

        call_user_func_array($this ->callback, array('r' => 0, 'key' => $this ->key, 'calltime' =>$calltime, 'data' =>$result));
	}

	/**
	 * [packHead 拼包头]
	 * @param  [type] $uin [description]
	 * @param  [type] $cmd [description]
	 * @return [type]      [description]
	 */
    private function packHead($uin, $cmd){

    	if (!isset($cmd) || $cmd == 0) {
    		return false;
    	}
        $this -> headConf['uin'] = $uin;
        $this -> headConf['cmd'] = $cmd;
 
        $headBuf = pack($this -> headConf['packFormat'],
                                $this -> headConf['version'],
                                $this -> headConf['packetType'],
                                $this -> headConf['bodyFormat'],
                                $this -> headConf['seqNo'],
                                $this -> headConf['uin'],
                                $this -> headConf['groupId'],
                                $this -> headConf['appId'],
                                $this -> headConf['appName'],
                                $this -> headConf['cmd'],
                                $this -> headConf['defaultErrCode'],
                                $this -> headConf['clientIp'],
                                $this -> headConf['serverIp'],
                                $this -> headConf['flags'],
                                $this -> headConf['reserved']);
        return $headBuf;
    }

    /**
     * [packBody 拼包体]
     * @param  [type] $jsonArr [description]
     * @return [type]          [description]
     */
    private function packBody($jsonArr){

    	//SysLog::info(__METHOD__." jsonArr = ".print_r($jsonArr,true), __CLASS__);
        $bodyBuf = $this -> jm ->to_binary($jsonArr);
        if (empty($bodyBuf)) {
        	echo "to binary failed \n";
        	//SysLog::error(__METHOD__. " to_binary body buf error", __CLASS__);
        }
        return $bodyBuf;
    }

    /**
     * [getResult 解析数据]
     * @return [type] [description]
     */
    public function getResult($rspHeadBuf, $rspBodyBuf){
        $rspHead = unpack('Cversion/CpacketType/CbodyFormat/NseqNo/Nuin/NgroupId/nappId/a32appName/ncmd/sdefaultErrCode/NclientIp/NserverIp/Nflags/Nreserved', $rspHeadBuf);
        $rspBody = $this -> jm -> to_array($rspBodyBuf);
        return array('r' => self::SUCCESS,
                     'rspHead' => $rspHead,
                     'rspBody' => $rspBody);
    }
   	
}