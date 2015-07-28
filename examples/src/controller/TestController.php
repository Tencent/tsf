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
		$response ->end(" test response 16".print_r($res, true));
		yield Swoole\Coroutine\SysCall::end('test for syscall end');
	}
	
	
	public function yieldTest(){
		$str = "1;test;hahhha,2;test2;heihei";
		foreach (explode(",", $str) as $line){
			$fileds = explode(";", $line);
			$id = array_shift($fileds);
			yield $id=>$fileds;
		}
	}
	
	private function test(){
		

		$test  = new TestModel();
		$res = (yield $test ->MysqlMuticallTest());
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
