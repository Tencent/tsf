<?php
/**
 * @Author: winterswang
 * @Date:   2015-07-03 17:49:43
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-07-03 19:36:08
 */

class TestController extends Controller{

	public function actionTest(){

		SysLog::info(__METHOD__, __CLASS__);
		$response = $this ->argv['response'];
		$res =(yield $this ->test());
		SysLog::debug(__METHOD__ ." res  == ".print_r($res, true), __CLASS__);
		$response ->end(" test response ");
	}
	
	public function actionProtocol(){
		
		$tcpReturn=(yield $this->tcpTest());
  		$udpReturn=(yield $this->udpTest());
  		$httpReturn=(yield $this->httpTest());
  		$response ->end(" test response ");
	}

	public function actionMuticall(){
  		$res = (yield $this->muticallTest());
  		$response ->end(" test response ");
	}


	public function tcpTest(){
	    $ip = '127.0.0.1';
	    $port = '9905';
	    $data = 'test';
	    $timeout = 0.5; //second
	    yield new Swoole\Client\TCP($ip, $port, $data, $timeout);
	 }

	  public function udpTest(){
	    $ip = '127.0.0.1';
	    $port = '9905';
	    $data = 'test';
	    $timeout = 0.5; //second
	    yield new Swoole\Client\UDP($ip, $port, $data, $timeout);
	  }

	  public function httpTest(){
	    $url='http://www.qq.com';
	    $httpRequest= new Swoole\Client\HTTP($url);
	    $data='testdata';
	    $header = array(
	      'Content-Length' => 12345,
	    );
	    yield $httpRequest->get($url); //yield $httpRequest->post($path, $data, $header);
	  }
	  
	  
	  public function muticallTest(){
	    $ip = '127.0.0.1';
	    $port = '9905';
	    $data = 'test';
	    $timeout = 0.5; //second
	    $calls=new Swoole\Client\Multi();
	    $firstReq=new Swoole\Client\TCP($ip, $port, $data, $timeout);
	    $secondReq=new Swoole\Client\UDP($ip, $port, $data, $timeout);
	    $calls ->request($firstReq,'first');             //first request
	    $calls ->request($secondReq,'second');             //second request
	    yield $calls;
	  }



	private function test(){

		$test  = new TestModel();
		$res = (yield $test ->testClient());
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
}
