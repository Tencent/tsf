<?php
/**
 * @Author: winterswang
 * @Date:   2015-06-27 15:35:48
 * @Last Modified by:   wangguangchao
 * @Last Modified time: 2015-07-09 19:02:11
 */

// 增加命名空间
namespace Swoole\Client;

//require_once "Base.php";

class HTTP extends Base {
    public $host, $port, $path;
    public $scheme;
    public $method;
    public $postdata = '';
    public $cookies = array();
    public $referer;
    public $accept = 'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,image/jpeg,image/gif,*/*';
    public $accept_encoding = 'gzip,deflate,sdch';
    public $accept_language = 'zh-CN,zh;q=0.8,en;q=0.6,zh-TW;q=0.4,ja;q=0.2';
    public $user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36';
    public $request_headers = array();
    // * Options:
    public $timeout = 20;
    public $use_gzip = true;
    public $persist_cookies = true;
    public $persist_referers = false;
    public $debug = false;
    public $handle_redirects = true;
    public $max_redirects = 5;
    public $headers_only = false;
    public $strict_redirects = false;
    // * Basic authorization variables:
    public $username, $password;
    // * Response vars:
    public $status;
    public $headers = array();
    public $rspHeaders = array();
    public $content = '';
    public $request = '';
    public $errormsg;
    // * Tracker variables:
    public $redirect_count = 0;
    public $callback;
    public $calltime;

    public function __construct($host){


        date_default_timezone_set('asia/shanghai');

        if (!isset($host)) {
           \SysLog::warn(__METHOD__." MISSING HOST",__CLASS__); 
        }
        $this ->referer = $host;
        $bits = parse_url($host);
        if(isset($bits['scheme']) && isset($bits['host'])) {
            $host = $bits['host'];
            $scheme = isset($bits['scheme']) ? $bits['scheme'] : 'http';
            $port = isset($bits['port']) ? $bits['port'] : 80;
            $path = isset($bits['path']) ? $bits['path'] : '';
            
            if (isset($bits['query']))
                $path .= '?'.$bits['query'];
        }
        $this->host = $host;
        $this->port = $port;
        if(isset($bits['scheme']) && isset($bits['host'])) {
            $this->setScheme($scheme);
            $this->setPath($path);
            $this->setMethod("GET");
        }
    }

    public function setRequestHeaders($array) {
        foreach($array as $key => $value) {
            $this->request_headers[$key] = $value;
        }
    }

    public function setMethod($method) {
        // Manually set the request method (not usually needed).
        if (!in_array($method, array("GET","POST","PUT","DEL"."ETE"))){
        	\SysLog::error(__METHOD__. ' valid method : '.$method, __CLASS__);
        	return false;
        }
        $this->method = $method;
        return true;
    }

    public function setPath($path) {
        // Manually set the path (not usually needed).
        $this->path = $path;
    }

    public function setTimeout($timeout){

        $this ->timeout = $timeout;
    }

    public function setUserAgent($string) {
        // Sets the user agent string to be used in the request.
        // Default is "Incutio HttpClient v$version".
        $this->user_agent = $string;
    }

    public function setAuthorization($username, $password) {
        // Sets the HTTP authorization username and password to be used in requests.
        // Warning: don't forget to unset this in subsequent requests to other servers!
        $this->username = $username;
        $this->password = $password;
    }

    public function setScheme($scheme) {
        // Manually set the path (not usually needed).
        switch($scheme) {
            case 'https':
                $this->scheme = $scheme;
                //TODO 暂不支持
                $this->port = 443;
                break;
            case 'http':
            default:
                $this->scheme = 'http';
        }
    }

    public function buildRequest(){

        $headers = array();
        $headers[] = "{$this->method} {$this->path} HTTP/1.1"; // * Using 1.1 leads to all manner of problems, such as "chunked" encoding
        $headers[] = "Host: {$this->host}";
        if ($this->referer)
            $headers[] = "Referer: {$this->referer}";
        // * Cookies:
        if (@$this->cookies[$this->host]) {
            $cookie = 'Cookie: ';
            foreach ($this->cookies[$this->host] as $key => $value) {
                $cookie .= "$key=$value; ";
            }
            //多了一个 ； 注意一下

            $headers[] = $cookie;
        }
        // * Basic authentication:
        if ($this->username && $this->password)
            $headers[] = 'Authorization: BASIC '.base64_encode($this->username.':'.$this->password);
        // * If this is a POST, set the content type and length:
        if(!empty($this->request_headers)) {
            foreach($this->request_headers as $key => $val) {
                if($val===false) {
                    // do nothing
                } else {
                    $headers[] = $key.': '.$val;
                }
            }
        }
        if ($this->use_gzip && !isset($this->request_headers['Accept-encoding']))
            $headers[] = "Accept-encoding: {$this->accept_encoding}";
        // If it is a POST, add Content-Type.
        if (!isset($this->request_headers['Content-Type']) &&
            $this->method == 'POST') {
            $headers[] = "Content-Type: application/x-www-form-urlencoded";
        }
        if (!isset($this->request_headers['User-Agent']))
            $headers[] = "User-Agent: {$this->user_agent}";
        if (!isset($this->request_headers['Accept']))
            $headers[] = "Accept: {$this->accept}";
        if (!isset($this->request_headers['Accept-language']))
            $headers[] = "Accept-language: {$this->accept_language}";
        if ($this->postdata && !isset($this->request_headers['Content-Length'])) {
            $headers[] = 'Content-Length: '.strlen($this->postdata);
        }
        $this ->request = implode("\r\n", $headers)."\r\n\r\n".$this->postdata; 	
    }

    public function buildQuery($data) {
        
        if(is_string($data)) {
            $this->postdata = $data;
            return true;
        } else if(is_object($data) || is_array($data)){
            $this->postdata = http_build_query($data);
            return true;
        } else {
            //trigger_error("HttpClient::postdata : '".gettype($data)."' is not valid post data.", E_USER_ERROR);
            return false;
        }
    }

    public function get($path, $data = null, $headers=array()){

        $this->orig_path = $this->path;
        if(!empty($this->path))
            $this->path .= $path;
        else
            $this->path = $path;
        $this->method = 'GET';
        if ($data) $this->path .= '?'.http_build_query($data);
        $this->setRequestHeaders($headers);
        $this->buildRequest();

        
        \SysLog::info(__METHOD__." GET RESULT = ". print_r($this,true), __CLASS__);
        yield $this;
    }

    public function post($path, $data, $headers=array()){

        $this ->orig_path = $this->path;
        if(!empty($this->path))
            $this->path .= $path;
        else
            $this->path = $path;
        $this->method = 'POST';
        $this->setRequestHeaders($headers);
        $this->buildQuery($data);
        $this->buildRequest();

        \SysLog::info(__METHOD__." POST RESULT = ". print_r($this,true), __CLASS__);
        yield $this;
    }

    public function send(callable $callback){

        $client = new  \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

        $client->on("connect", function($cli){
            $cli->send($this ->request);
        });

        $client->on('close', function($cli){
        });

        $client->on('error', function($cli) use($callback){
            $cli ->close();
            call_user_func_array($callback, array('r' => 1, 'key' => $this ->key,  'error_msg' => 'conncet error'));
        });

        $client->on("receive", function($cli, $data) use($callback){
            call_user_func_array(array($this, 'packRsp'), array('r' => 0, 'key' => $cli, 'data' =>$data));
        });

        $this ->callback = $callback;
        if($client->connect($this ->host, $this ->port, $this ->timeout)){//同步调用 优化下？ flag = 1
            $this ->calltime = microtime(true);
        }
    }
    
    public function packRsp($r,$k,$data){

    	if ($r != 0) {
    		//LOG
    		return;
    	}

        $this ->content .= $data;

    	if (empty($this ->rspHeaders)) {
    		$this ->parseHeader($data);
    	}
    	
        $body_length = strlen($this ->content);
        //判断包是否收全
        //Content-Length
        if (isset($this ->respHeader['Content-Length']) && $body_length == $this ->respHeader['Content-Length']) {


            \SysLog::info(__METHOD__. "callback = ".print_r($this ->callback,true), __CLASS__);
            \SysLog::info(__METHOD__." pack finish body === ".$this ->content, __CLASS__);

            $data=array('head'=>$this->respHeader,'body'=>$this ->content);

            $k ->close();
            $this ->calltime = microtime(true) - $this ->calltime;
            call_user_func_array($this ->callback, array('r' => 0, 'key' => '','calltime' => $this ->calltime, 'data' =>$data));
        }
        else{
            \SysLog::info('header content-lengh = '.$this ->respHeader['Content-Length'] . ' content length == '. strlen($this ->content), __CLASS__);
        }

        //chunked 
        if (isset($this->respHeader['Transfer-Encoding']) and $this->respHeader['Transfer-Encoding'] == 'chunked') {

            //以\r\n分割为两个数组，第一部分是字节长，第二部分为body
            //字节长不为零，合并body，字节长为零，合并body，返回
            $parts = explode("\r\n", $data, 2);

            if (intval($parts[0]) != 0 && isset($parts[1])) {
                $this ->content .= $parts[1];
                \SysLog::info('chunked packing content  == '. $this ->content, __CLASS__);
            }
            else{
                \SysLog::info('chunked pack finish content  == '. $this ->content, __CLASS__);
                $data=array('head'=>$this->respHeader,'body'=>$this ->content);

                $k ->close();
                $this ->calltime = microtime(true) - $this ->calltime;
                call_user_func_array($this ->callback, array('r' => 0, 'key' => '','calltime' => $this ->calltime, 'data' =>$data));
            }
        }

    }

    private function parseHeader($data){

    	$parts = explode("\r\n\r\n", $data, 2);
		$headerLines = explode("\r\n", $parts[0]);
		list($this ->rspHeaders['method'], $this ->rspHeaders['uri'], $this ->rspHeaders['protocol']) = explode(' ', $headerLines[0], 3);

        $this->respHeader =  \Swoole\Http\Parser::parseHeaderLine($headerLines);

        if (isset($parts[1])) {
            $this ->content = $parts[1];
        }

        //print_r($this ->respHeader);
        \SysLog::info(__METHOD__." header == ".print_r($this ->respHeader,true), __CLASS__);
    }

}