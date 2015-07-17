<?php
/**
 * @Author: winterswang
 * @Date:   2015-07-15 21:16:41
 * @Last Modified by:   wangguangchao
 * @Last Modified time: 2015-07-17 20:48:34
 */
namespace Swoole\Client;

require_once "Base.php";
require_once "Timer.php";
require_once "../test/SysLog.php";

class HTTPNEW extends Base {

	public $accept = 'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,image/jpeg,image/gif,*/*';
	public $acceptLanguage = 'zh-CN,zh;q=0.8,en;q=0.6,zh-TW;q=0.4,ja;q=0.2';
	public $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36';
	public $acceptEncoding = 'gzip,deflate,sdch';

	public $requestHeaders = array();
	public $rspHeaders = array();

	public $persistReferers = false; //
	public $handleRedirects = false; //暂不支持重定向
	public $redirectCount = 0;
	public $maxRedirects = 5;
	public $persistCookies = false; //cookie 

	public $username;
	public $password;
	public $calltime;
	public $callback;
	public $timeout;
	public $postdata;

	public $cookies = array();
	public $request = '';
	public $method;
	public $useGzip = true;
	public $referer;

	public $contents = '';
	public $host;
	public $port;
	public $path;
	public $key;

	private $firstRsp = true;

	/**
	 * [__construct 构造函数]
	 * @param [type] $referer [description]
	 */
	public function __construct($uri){

		$this ->referer = $uri;

		if (empty($uri)) {
			return;
		}
		$info = parse_url($uri);

		//port
		$this ->port = isset($info['port']) ? $info['port'] : 80;
		// scheme
		if (!isset($info['scheme'])) {

			\SysLog::error(__METHOD__ . " miss scheme ", __CLASS__);
			return false;
		}
		if ('https' === $info['scheme']) {
			$this ->port = 443;
		}
		//host
		if (!isset($info['host'])) {
			
			\SysLog::error(__METHOD__ . " miss host ", __CLASS__);
			return false;
		}
		$this ->host = $info['host'];
		$this ->key = md5($uri . microtime(true) . rand(0,10000));
	}

	/**
	 * [get get方法]
	 * @param  [type] $path    [description]
	 * @param  array  $data    [description]
	 * @param  array  $headers [description]
	 * @return [type]          [description]
	 */
	public function get($path, $data = array(), $headers = array()){

		/*
			拼接url,组装header信息，发送请求
		 */
		$this ->method = 'GET';
		$this ->path = $path;

		//拼接请求数据
		if (!empty($data)) {
			$this ->path .= http_build_query($data);
		}

		//设置请求headers信息
		if (!empty($headers)) {
			$this ->requestHeaders = $this ->setRequestHeaders($headers);
		}

		$this ->buildRequest();
	//	\SysLog::debug(__METHOD__ . " httpclient == " .print_r($this, true), __CLASS__);
		return $this;
	}

	/**
	 * [post post方法]
	 * @param  [type] $path    [description]
	 * @param  [type] $data    [description]
	 * @param  [type] $headers [description]
	 * @return [type]          [description]
	 */
	public function post($path, $data, $headers){

		$this ->method = 'POST';
        $this ->setRequestHeaders($headers);

        $this ->buildQuery($data);
        $this ->buildRequest();

        \SysLog::debug(__METHOD__ . " httpclient == " .print_r($this, true), __CLASS__);
        return $this;
	}

	/**
	 * [useGzip 是否压缩]
	 * @param  [type] $boolean [description]
	 * @return [type]          [description]
	 */
	public function useGzip($boolean){

		$this ->useGzip = $boolean;
	}

	/**
	 * [setUserAgent 设置代理]
	 * @param [type] $string [description]
	 */
	public function setUserAgent($string) {

		$this->user_agent = $string;
	}
	
	/**
	 * [setAuthorization 设置权限]
	 * @param [type] $username [description]
	 * @param [type] $password [description]
	 */
	public function setAuthorization($username, $password) {

		$this->username = $username;
		$this->password = $password;
	}	

	/**
	 * [getCookies 获取cookies]
	 * @param  [type] $host [description]
	 * @return [type]       [description]
	 */
	public function getCookies($host = null) {
		
		if(isset($this->cookies[isset($host) ? $host : $this->host])){

			return $this->cookies[isset($host) ? $host : $this->host];
		}
		return array();
	}
	
	/**
	 * [setCookies 设置cookies]
	 * @param [type]  $array   [description]
	 * @param boolean $replace [description]
	 */
	public function setCookies($array, $replace = false) {
		
		if ($replace || (!isset($this ->cookies[$this ->host])) || (!is_array($this->cookies[$this->host]))){

			$this->cookies[$this->host] = array();
		}

		$this->cookies[$this->host] = array_merge($array, $this ->cookies[$this ->host]);
	}

	/**
	 * [setPersistReferers 设置重定向时，是否保持referer]
	 * @param [type] $boolean [description]
	 */
	public function setPersistReferers($boolean) {

		$this->persistReferers = $boolean;
	}
	
	/**
	 * [setHandleRedirects 设置是否支持重定向]
	 * @param [type] $boolean [description]
	 */
	public function setHandleRedirects($boolean) {

		$this->handleRedirects = $boolean;
	}
	
	/**
	 * [setMaxRedirects 设置重定向总次数]
	 * @param [type] $num [description]
	 */
	public function setMaxRedirects($num) {

		$this->maxRedirects = $num;
	}

	/**
	 * [setPersistCookies 设置cookie保持]
	 * @param [type] $boolean [description]
	 */
	public function setPersistCookies($boolean) {
		
		$this->persistCookies = $boolean;
		
	}

	/**
	 * [send 异步IO，定时器设置，异常回调]
	 * @param  callable $callback [description]
	 * @return [type]             [description]
	 */
	public function send(callable $callback){

        $client = new  \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

        $client->on("connect", function($cli){
            $cli->send($this ->request);
        });

        $client->on('close', function($cli){
        });

        $client->on('error', function($cli) use($callback){
            
            $cli ->close();
            $this ->calltime = microtime(true) - $this ->calltime;
            call_user_func_array($callback, array('r' => 1, 'key' => $this ->key, 'calltime' => $this ->calltime, 'error_msg' => 'conncet error'));
        });

        $client->on("receive", function($cli, $data) use($callback){
            /*
                这里的on receivce会被触发多次，耗时和取消定时器都不在这里处理，在packRsp函数里
             */
            call_user_func_array(array($this, 'packRsp'), array('key' => $cli, 'data' => $data));
        });

        $this ->callback = $callback;
        if($client->connect($this ->host, $this ->port, $this ->timeout)){

            $this ->calltime = microtime(true);
            if (floatval(($this ->timeout)) >0) {
                Timer::add($this ->key, $this ->timeout, $client, $callback, array('r' => 2 ,'key' => $this ->key, 'calltime' => $this ->calltime, 'error_msg' => $this ->host . ':'. $this ->port .' timeout'));
            }
        }
	}

	/**
	 * [setTimeout 定时]
	 * @param [type] $timeout [description]
	 */
    public function setTimeout($timeout){

        $this ->timeout = $timeout;
    }

	/**
	 * [buildQuery description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
    private function buildQuery($data) {
        
        if(is_string($data)) {

            $this->postdata = $data;
            return true;
        } 
        else if(is_object($data) || is_array($data)){

            $this->postdata = http_build_query($data);
            return true;
        } 
        else {
            return false;
        }
    }

	/**
	 * [packRsp 组包合包，函数回调]
	 * @param  [type] $cli  [description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	private function packRsp($cli, $data){

		/*
			1.设置标记位，开始时，解析头部信息
			2.合并boty，两种头部协议
			3.特殊处理 重定向+超时
		 */
		

		//parse header first
		if ($this ->firstRsp) {
			$rsp = explode("\r\n\r\n", $data, 2);
			$this ->parseHeader($rsp[0]);
			$this ->firstRsp = false;
			$data = $rsp[1];
		}

		//编码
		if (isset($this->rspHeaders['Content-Encoding']) && $this->rspHeaders['Content-Encoding'] == 'gzip') {

			//$data = substr($data, 10);
			//$data = gzinflate($data);
		}
		//cookie 保持
		if ($this->persistCookies && isset($this->rspheaders['set-cookie'])) {

			//TODO support
		}

		if ($this->handleRedirects) {

			if (++ $this->redirectCount >= $this ->maxRedirects) {

				\SysLog::error(__METHOD__ . " redirectCount over limit ", __CLASS__);
				return false;
			}
			
			$location = isset($this->rspheaders['location']) ? $this->rspheaders['location'] : '';
			$location .= isset($this->rspheaders['uri']) ? $this->rspheaders['uri'] : '';

			if (isset($location) && $this ->rspHeaders['status'] >= 300 && $this ->rspHeaders['status'] <= 400) {

				\SysLog::debug(__METHOD__ . " redirect location ", __CLASS__);
				//TODO 尝试client内部重定
				$url = parse_url($location);
				$this ->host = isset($url['host']) ? $url['host'] : $this ->host;
				$this ->contents = '';

				$http = $this ->get($location);
				$this ->send(array($this, 'packRsp'));
				return ;
			}
		}

		//拼包 Content-Length	
		if (isset($this ->rspHeaders['Content-Length'])) {
			
			$this ->contents .= $data;
			$bodyLength = strlen($this ->contents);
			if ($bodyLength == $this ->rspHeaders['Content-Length']) {
				//pack finish
				//TODO 回射数据
                \SysLog::info('Content-Length pack finish content  == '. $this ->contents, __CLASS__);
                $data = array('head' => $this ->rspHeaders, 'body' => $this ->contents);
            
                $cli ->close();
                $this ->calltime = microtime(true) - $this ->calltime;
                //echo " Content-Length pack finish \n";
                call_user_func_array($this ->callback, array('r' => 0, 'key' => $this ->key,'calltime' => $this ->calltime, 'data' =>$data));
			}else{
				//echo " Content-Length packing \n";
			}
		}


		//拼包 chunked 
		if (isset($this->rspHeaders['Transfer-Encoding']) and $this->rspHeaders['Transfer-Encoding'] == 'chunked') {
	
            if (!preg_match("/0\\r\\n\\r\\n/", $data)) {
            	$parts = explode("\r\n", $data, 2);
                $this ->contents .= $parts[1];
            }
            else{
                
                $data = str_replace("0\r\n\r\n", "", $data);
                $this->contents .=$data;
                $data=array('head' => $this->respHeader, 'body'=> $this ->contents);
            
            	Timer::del($this ->key);
                $cli ->close();
                $this ->calltime = microtime(true) - $this ->calltime;
                call_user_func_array($this ->callback, array('r' => 0, 'key' => $this ->key,'calltime' => $this ->calltime, 'data' =>$data));
            }			
		}
		
	}

	/**
	 * [setRequestHeaders 设置请求的headers]
	 * @param array $headers [description]
	 */
	private function setRequestHeaders($headers = array()){

		foreach($headers as $h_k => $h_v) {

            $this->requestHeaders[$h_k] = $h_v;
        }
	}

	/**
	 * [buildRequest 创建request信息]
	 * @return [type] [description]
	 */
	private function buildRequest(){
		
		$headers = array();
		$headers[] = "{$this->method} {$this->path} HTTP/1.1";
		$headers[] = "Host: {$this->host}";
		$headers[] = "User-Agent: {$this->userAgent}";
		$headers[] = "Accept: {$this->accept}";
		
		if (isset($this->useGzip)){

			$headers[] = "Accept-encoding: {$this->acceptEncoding}";
		}
		
		$headers[] = "Accept-language: {$this->acceptLanguage}";
		
		if (isset($this->referer)){

			$headers[] = "Referer: {$this->referer}";
		}
		
		if (isset($this->cookies[$this->host])) {
			$cookie = 'Cookie: ';
			foreach ($this->cookies[$this->host] as $key => $value) {
				$cookie .= "$key=$value; ";
			}
			$headers[] = $cookie;
		}
		
		if (isset($this->username) && isset($this->password)){

			$headers[] = 'Authorization: BASIC '.base64_encode($this->username.':'.$this->password);
		}
		
		if ($this->postdata) {
			$headers[] = 'Content-Type: application/x-www-form-urlencoded';
			$headers[] = 'Content-Length: '.strlen($this->postdata);
		}

		//将用户设置的header信息覆盖默认值
		foreach ($this ->requestHeaders as $h_k => $h_v) {
			
			$headers[$h_k] = $h_v;
		}
		
		$this ->request = implode("\r\n", $headers)."\r\n\r\n".$this->postdata;
	
	}

	/**
	 * [parseHeader description]
	 * @param  [type] $headerBuf [description]
	 * @return [type]            [description]
	 */
	private function parseHeader($headerBuf){

		/*
			version + status_code + message
		 */
		$headParts = explode("\r\n", $headerBuf);
        if (is_string($headParts))
        {
            $headParts = explode("\r\n", $headParts);
        }

		if (!is_array($headParts) || !count($headParts)) {
			
			//TODO header buffer valid
			return false;
		}

		list($this ->rspHeaders['protocol'], $this ->rspHeaders['status'], $this ->rspHeaders['msg']) = explode(' ', $headParts[0], 3);
		unset($headParts[0]);

		foreach ($headParts as $header) {
			
			$header = trim($header);
			if (empty($header)) {
				continue;
			}

			$h = explode(':', $header, 2);
			$key = trim($h[0]);
			$value = trim($h[1]);
			$this ->rspHeaders[$key] = $value;
		}

		\SysLog::debug(__METHOD__ . " header == " . print_r($this ->rspHeaders,true), __CLASS__);
	}

	// public function test($r, $k ,$ct, $data){

	// 	echo " r == $r k == $k ct == $ct \n";
	// 	file_put_contents('/tmp/test.html', $data[1]);
	// }
}


