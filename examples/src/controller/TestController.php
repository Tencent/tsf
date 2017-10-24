<?php
/**
 * Tencent is pleased to support the open source community by making TSF Solution available.
 * Copyright (C) 2017 THL A29 Limited, a Tencent company. All rights reserved.
 * Licensed under the BSD 3-Clause License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * https://opensource.org/licenses/BSD-3-Clause
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
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
}
