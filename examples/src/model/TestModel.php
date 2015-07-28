<?php
/**
 * @Author: winterswang
 * @Date:   2015-07-03 18:10:05
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-07-10 11:02:01
 */


class TestModel {

	public function test(){

		yield array('r' => 0, 'data' => 'yield test');
	}

	public function udpTest(){

		// send data to back server 
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
	    yield $httpRequest->get($url); 
	    //yield $httpRequest->post($path, $data, $header);
	  }
	  
	public function muticallTest(){
	    $ip = '127.0.0.1';
	    $data = 'test';
	    $timeout = 0.5; //second

	    $calls=new Swoole\Client\Multi();

	    $firstReq=new Swoole\Client\TCP($ip, '9905', $data, $timeout);
	    $secondReq=new Swoole\Client\UDP($ip, '9904', $data, $timeout);

	    $calls ->request($firstReq,'first');             //first request
	    $calls ->request($secondReq,'second');             //second request

	    yield $calls;
	  }


	public function HttpmuticallTest(){
	      $calls=new Swoole\Client\Multi();
	      $qq = new Swoole\Client\HTTP("http://www.qq.com/");
	      $oschina = new Swoole\Client\HTTP("http://www.oschina.net/");
	      $calls ->request($qq,"qq");
	      $calls ->request($oschina,"oschina");
	      yield $calls;
	}

	//db pool muticall test
	public function MysqlMuticallTest(){
		$calls=new Swoole\Client\Multi();
		$select = new Swoole\Client\DB("select * from test");
		$desc = new Swoole\Client\DB("desc test");
		$calls ->request($select,"select");
		$calls ->request($desc,"desc");
		yield $calls;
	}

    //db pool  test
    public function Dbtest(){
        $db = new Swoole\Client\DB();
        yield $db->query("select * from test");
    }
	
	

	public function tcpTest(){
	    $ip = '127.0.0.1';
	    $port = '9905';
	    $data = 'test';
	    $timeout = 0.5; //second
	    yield new Swoole\Client\TCP($ip, $port, $data, $timeout);
	 }

	 public function mysqlTest(){
	 	$sql = new Swoole\Client\MYSQL(array('host' => '127.0.0.1', 'port' => 3345, 'user' => 'root', 'password' => 'root', 'database' => 'test', 'charset' => 'utf-8',));
		$ret = (yield $sql ->query('show tables'));
		var_dump($ret);
		$ret = (yield $sql ->query('desc test'));
		var_dump($ret);
	 }
}