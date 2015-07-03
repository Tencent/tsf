<?php
/**
 * @Author: winterswang
 * @Date:   2015-07-03 18:10:05
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-07-03 19:57:47
 */


class TestModel {

	public function test(){

		yield array('r' => 0, 'data' => 'yield test');
	}

	public function testClient(){

		// send data to back server 
		$ip = '127.0.0.1';
		$port = '9905';
		$data = 'test';
		$timeout = 0.5; //second
		yield new Swoole\Client\UDP($ip, $port, $data, $timeout);
	}
}