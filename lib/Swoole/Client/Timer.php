<?php
/**
 * @Author: wangguangchao
 * @Date:   2015-07-06 19:57:07
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-07-07 15:40:03
 */

namespace Swoole\Client;

class Timer {

	protected static $event = array();
	protected static $tickKey;
	
	const CONNECT = 1;
	const RECEIVE = 2;

	const LOOPTIME  = 500; //设置loop循环的时间片
	/**
	 * [add 添加IO事件]
	 * @param [type] $socket   [fd]
	 * @param [type] $timeout  [second]
	 * @param [type] $cli      [description]
	 * @param [type] $callback [description]
	 */
	public static function add($key, $timeout, $cli, $callback, $params){

		\SysLog::info(__METHOD__ . " key == $key " , __CLASS__);

		self::init();

		$event = array(
			'timeout' => microtime(true),
			'cli' => $cli,
			'callback' => $callback,
			'params' => $params,
			);
	
		self::$event[$key] = $event;
	}


	public static function del($key){

		\SysLog::info(__METHOD__ . " del event key == $key ", __CLASS__);
		if (isset(self::$event[$key])) {

			unset(self::$event[$key]);
		}
	}

	public static function loop(){

		\SysLog::info(__METHOD__, __CLASS__);
		/*
			遍历自己的数组，发现时间超过预定时间段，且该IO的状态依然是未回包状态，则走超时逻辑
		 */
		foreach (self::$event as $socket => $e) {

		    $res = (microtime(true) - $e['timeout'])* 1000 - self::LOOPTIME;
		    \SysLog::debug(__METHOD__ ." key == $socket res == ".$res, __CLASS__);
		     
 		    if($res >= 0){

			self::del($socket);
			$cli = $e['cli'];
			$cli ->close();

		        call_user_func_array($e['callback'], $e['params']);
	            }
		}
	}

	/**
	 * [init 启动定时器]
	 * @return [type] [description]
	 */
	public static function init(){

		if (!isset(self::$tickKey)) {

			self::$tickKey = swoole_timer_tick(self::LOOPTIME, function(){

			    //循环数组，踢出超时情况
			    self::loop();
			});
			\SysLog::info(__METHOD__ ." init timer tick key == " . self::$tickKey, __CLASS__);
		}


	}

}
