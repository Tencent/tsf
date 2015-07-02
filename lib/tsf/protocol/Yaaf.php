<?php
/**
 * @Author: winterswang
 * @Date:   2015-06-03 15:17:25
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-06-03 21:39:49
 */

require_once "JsonManager.php";

class Yaaf {

	const HEADLEN = 69;
    const SUCCESS = 0;
    const ERROR_UIN = 11;
    const ERROR_CMD = 12;
    const ERROR_PACK_HEAD = 13;
    const ERROR_JSON = 21;
    const ERROR_PACK_BODY = 22;
    const ERROR_PACK_BUF = 31;
    const ERROR_SENDRECV = 41;

    public static $jsonManager;

    public static function getJsonManager(){

    	if (!isset(self::$jsonManager) || !is_object(self::$jsonManager)) {
    		self::$jsonManager = new JsonManager();
    	}
    	return self::$jsonManager;
    }

    public static function packHeader($data = array()){

    	/*
    	拼接header
    	 */
    	  	
		$data = array(
            'packFormat' => isset($data['packFormat']) ? $data['packFormat'] : 'CCCNNNna32nsNNNN',
            'version' => isset($data['version']) ? $data['version'] : 1,
            'packetType' => isset($data['packetType']) ? $data['packetType'] : 0,
            'bodyFormat' => isset($data['bodyFormat']) ? $data['bodyFormat'] : 0,
            'seqNo' => isset($data['seqNo']) ? $data['seqNo'] : 1,
            'uin' => isset($data['uin']) ? $data['uin'] : 0,
            'groupId' => isset($data['groupId']) ? $data['groupId'] : 0,
            'appId' => isset($data['appId']) ? $data['appId'] : 0,
            'appName' => isset($data['appName']) ? $data['appName'] : '',
            'cmd' => isset($data['cmd']) ? $data['cmd'] : 0,
            'defaultErrCode' => isset($data['defaultErrCode']) ? $data['defaultErrCode'] : 0,
            'clientIp' => isset($data['clientIp']) ? $data['clientIp'] : 0,
            'serverIp' => isset($data['serverIp']) ? $data['serverIp'] : 0,
            'flags' => isset($data['flags']) ? $data['flags'] : 0,
            'reserved' => isset($data['reserved']) ? $data['reserved'] : 0,
        );

    	return pack($data['packFormat'],
                    $data['version'],
                    $data['packetType'],
                    $data['bodyFormat'],
                    $data['seqNo'],
                    $data['uin'],
                    $data['groupId'],
                    $data['appId'],
                    $data['appName'],
                    $data['cmd'],
                    $data['defaultErrCode'],
                    $data['clientIp'],
                    $data['serverIp'],
                    $data['flags'],
                    $data['reserved']);
    }

    public static function packBody($data = array()){
    	/*
		拼接body    	
    	 */
    	return self::getJsonManager() ->to_binary($data);
    }

    public static function unpackHeader($data){

    }

    public static function unpackBody($data){

    }

    public static function unpackAll($data){

	    $reqHeadBuf = substr($data, 0, self::HEADLEN);
        $reqBodyBuf = substr($data, self::HEADLEN);
        $reqHead = unpack('Cversion/CpacketType/CbodyFormat/NseqNo/Nuin/NgroupId/nappId/a32appName/ncmd/sdefaultErrCode/NclientIp/NserverIp/Nflags/Nreserved', $reqHeadBuf);
        $reqBody = self::getJsonManager() -> to_array($reqBodyBuf);
        return array('r' => self::SUCCESS,
                     'reqHead' => $reqHead,
                     'reqBody' => $reqBody);
    }

}