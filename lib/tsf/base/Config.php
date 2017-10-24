<?php

/**
 * Tencent is pleased to support the open source community by making TSF Solution available.
 * Copyright (C) 2017 THL A29 Limited, a Tencent company. All rights reserved.
 * Licensed under the BSD 3-Clause License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * https://opensource.org/licenses/BSD-3-Clause
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 */
class Config
{

    private static $configCache;

    /**
     * [load 加载配置文件信息到类变量]
     * @param  [type] $filename [文件名]
     * @return [type]           [description]
     */
    public static function load($serverName)
    {
        if (!isset($configCache[$serverName]) && empty(self::$configCache[$serverName])) {
            $config = require(TSFBASEPATH . '/tsf/' . $serverName . '/Config/Config.php');
            self::$configCache[$serverName] = $config;
        }
    }


    /**
     * [getConfig 获取配置文件的配置信息]
     * @param  [type] $filename [配置文件名]
     * @param  [type] $key      [数组的KEY]
     * @return [type]           [description]
     */
    public static function getConfig($serverName, $key)
    {
        //TODO 日志
        error_log(__METHOD__ . " serverName : $serverName and key : $key" . PHP_EOL, 3, '/tmp/winters.log');

        if (!isset(self::$configCache[$serverName])) {
            self::load($serverName);
        }

        if (!$key) {
            return isset(self::$configCache[$serverName]) ? self::$configCache[$serverName] : null;
        } else {
            return isset(self::$configCache[$serverName][$key]) ? self::$configCache[$serverName][$key] : null;
        }
    }



    /**
     * [getConfig 获取配置文件的配置信息]
     * @param  [type] $filename [配置文件名]
     * @param  [type] $key      [数组的KEY]
     * @return [type]           [description]
     */
//	public static function getConfig($filename,$key =null){
//		//TODO 日志
//		error_log(__METHOD__." filename : $filename and key : $key".PHP_EOL,3,'/tmp/winters.log');
//
//		if(!isset(self::$configCache[$filename])){
//			self::load($filename);
//		}
//
//		if(!$key){
//			return isset(self::$configCache[$filename]) ? self::$configCache[$filename]: null;
//		}else{
//			return isset(self::$configCache[$filename][$key]) ? self::$configCache[$filename][$key]: null;
//		}
//	}

    /**
     * [getErrorCon 获取错误码对应的提示信息]
     * @param  [type] $className [类名]
     * @param  [type] $errorCode [错误号]
     * @return [type]            [description]
     */
//	public static function getErrorCon($className,$errorCode){
//
//		$arr = self::getConfig('ErrorCon',$className);
//		if (empty($arr)) {
//			//TODO 日志
//			return 'errorInfo not found';
//		}
//		return $arr[$errorCode];
//	}

    /**
     * [getCmdCon 获取命令号对应的配置信息]
     * @param  [type] $cmd [命令号]
     * @return [type]      [description]
     */
//	public static function getCmdCon($cmd){
//
//		$arr = self::getConfig('CmdCon',$cmd);
//		if (empty($arr)) {
//			error_log(__METHOD__.' get cmdconf failed cmd : '.$cmd.PHP_EOL,3,'/tmp/winters.log');
//			return false;
//		}
//		error_log(__METHOD__.' arr : ' . print_r($arr,true),3,'/tmp/winters.log');
//		return $arr;
//	}
}

?>