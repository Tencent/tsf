<?php
/**
 * @Author: winterswang
 * @Date:   2015-07-03 17:49:43
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-07-04 23:46:34
 */

class TestController extends Controller{

	public function actionTest(){

		SysLog::info(__METHOD__, __CLASS__);
		$response = $this ->argv['response'];
		$res =(yield $this ->test());
		SysLog::debug(__METHOD__ ." res  == ".print_r($res, true), __CLASS__);
		$response ->end(" test response ");
		yield Swoole\Coroutine\SysCall::end('test for syscall end');
	}
	
	private function test(){

		$test  = new TestModel();
		$res = (yield $test ->udpTest());
		SysLog::info(__METHOD__ . " res == " .print_r($res, true), __CLASS__);
		if ($res['r'] == 0) {

			//yield success
			SysLog::info(__METHOD__. " yield success data == " .print_r($res['data'], true), __CLASS__);
			yield $res;
		}
		else{

			//yield failed
			SysLog::error(__METHOD__ . " yield failed res == " .print_r($res, true), __CLASS__);
			yield array(
				'r' => 1,
				'error_msg' => 'yield failed',
				 );
		}
	}
	
		public function actionMutical(){

		$response = $this ->argv['response'];
		$ip = '127.0.0.1';
		$data = 'test';
		$timeout = 0.5; //second
		$calls=new Swoole\Client\Multi();
		$firstReq=new Swoole\Client\TCP($ip, '9905', $data, $timeout);
		$secondReq=new Swoole\Client\UDP($ip, '9904', $data, $timeout);
		$calls ->request($firstReq,'first');             //first request
		$calls ->request($secondReq,'second');             //second request
		$ret=(yield $calls);
		$response ->end(" test response ");

	}


	public function actionTest(){
		$response = $this ->argv['response'];
		$ip = '127.0.0.1';
		$port = '9905';
		$data = 'test';
		$timeout = 0.5; //second
		$ret=(yield new Swoole\Client\UDP($ip, $port, $data, $timeout));
		$ret=(yield new Swoole\Client\TCP($ip, $port, $data, $timeout));
		$url='http://www.qq.com';
		$httpRequest= new Swoole\Client\HTTP($url);
		$ret=(yield $httpRequest->get($url)); //yield $httpRequest->post($path, $data, $header);
		$response ->end(" test response ");
	}
}
