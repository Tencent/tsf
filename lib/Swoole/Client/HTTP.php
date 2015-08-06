<?php
/**
 * @Author: winterswang
 * @Date:   2015-07-15 21:16:41
 * @Last Modified by:   wangguangchao
 * @Last Modified time: 2015-07-17 20:56:57
 */
namespace Swoole\Client;

class HTTP extends Base
{

    public $accept = 'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,image/jpeg,image/gif,*/*';
    public $acceptLanguage = 'zh-CN,zh;q=0.8,en;q=0.6,zh-TW;q=0.4,ja;q=0.2';
    public $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36';
    public $acceptEncoding = 'gzip,deflate,sdch';

    public $requestHeaders = array();
    public $rspHeaders = array();

    public $persistReferers = false; //
    public $handleRedirects = true; //重定向
    public $redirectCount = 0;
    public $maxRedirects = 5;
    public $persistCookies = false; //cookie

    public $username;
    public $password;
    public $calltime;
    public $callback;
    public $timeout = 10;
    public $postdata;

    public $cookies = array();
    public $request = '';
    public $method;
    public $useGzip = true;
    public $referer;

    public $body = '';
    public $url;
    public $host;
    public $port;
    public $path;
    public $key;

    protected $buffer = '';
    protected $isError = false;
    protected $isFinish = false;
    protected $status = array();
    protected $trunk_length = 0;

    /**
     * [__construct 构造函数]
     * @param string $url
     * @param string $method
     * @param string||array $data
     * @param int $timeout Second
     */
    public function __construct($url, $method = "GET", $data = NULL, $timeout = NULL)
    {

        if (empty($url)) {
            return;
        }

        $this->url = $this->referer = $url;
        $this->method = $method;

        if ($timeout) {
            $this->timeout = $timeout;
        }

        $info = parse_url($url);

        //port
        $this->port = isset($info['port']) ? $info['port'] : 80;
        // scheme
        if (!isset($info['scheme'])) {
            \SysLog::error(__METHOD__ . " miss scheme ", __CLASS__);
            return false;
        }
        if ('https' === $info['scheme']) {
            $this->port = 443;
        }
        //host
        if (!isset($info['host'])) {

            \SysLog::error(__METHOD__ . " miss host ", __CLASS__);
            return false;
        }
        $this->host = $info['host'];

        //path
        $this->path = isset($info['path']) ? $info['path'] : "/";

        //request data
        if (!empty($data)) {
            $this->buildQuery($data);
        }

        $this->buildRequest();

    }

    /**
     * @desc set key
     * @param string $key
     * @return
     */
    public function setKey($key = NULL)
    {
        if (empty($key)) {
            $this->key = md5($this->url . microtime(true) . rand(0, 10000));
        } else {
            $this->key = $key;
        }
    }

    /**
     * @desc get key
     * @return string $this->key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @desc 数据解析
     * @return boolean
     */
    public function parseBody()
    {
        //解析trunk
        if (isset($this->rspHeaders['transfer-encoding']) and $this->rspHeaders['transfer-encoding'] == 'chunked') {
            while (1) {
                if ($this->trunk_length == 0) {
                    $_len = strstr($this->buffer, "\r\n", true);
                    if ($_len === false) {
                        return false;
                    }
                    $length = hexdec($_len);
                    if ($length == 0) {
                        $this->isFinish = true;
                        return true;
                    }
                    $this->trunk_length = $length;
                    $this->buffer = substr($this->buffer, strlen($_len) + 2);
                } else {
                    //数据量不足，需要等待数据
                    if (strlen($this->buffer) < $this->trunk_length) {
                        return false;
                    }
                    $this->body .= substr($this->buffer, 0, $this->trunk_length);
                    $this->buffer = substr($this->buffer, $this->trunk_length + 2);
                    $this->trunk_length = 0;
                }
            }
            return false;
        } //普通的Content-Length约定
        else {
            if (strlen($this->buffer) < $this->rspHeaders['content-length']) {
                return false;
            } else {
                $this->body = $this->buffer;
                $this->isFinish = true;
                return true;
            }
        }
    }

    /**
     * @desc 数据解压
     * @param $data
     * @return string $data
     */
    static function gz_decode($data, $type = 'gzip')
    {
        if ($type == 'gzip') {
            return gzdecode($data);
        } elseif ($type == 'deflate') {
            return gzinflate($data);
        } elseif ($type == 'compress') {
            return gzinflate(substr($data, 2, -4));
        } else {
            return $data;
        }
    }

    /**
     * [get get方法]
     * @param  [type] $path    [description]
     * @param  array $data [description]
     * @param  array $headers [description]
     * @return [type]          [description]
     */
    public function get($path, $data = array(), $headers = array())
    {

        /*
            拼接url,组装header信息，发送请求
         */
        $this->method = 'GET';
        $this->path = $path;

        //拼接请求数据
        if (!empty($data)) {
            $this->path .= http_build_query($data);
        }

        //设置请求headers信息
        if (!empty($headers)) {
            $this->setRequestHeaders($headers);
        }

        $this->buildRequest();
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
    public function post($path, $data, $headers)
    {

        $this->method = 'POST';
        $this->path = $path;
        $this->setRequestHeaders($headers);

        $this->buildQuery($data);
        $this->buildRequest();

        \SysLog::debug(__METHOD__ . " httpclient == " . print_r($this, true), __CLASS__);
        return $this;
    }

    /**
     * [useGzip 是否压缩]
     * @param  [type] $boolean [description]
     * @return [type]          [description]
     */
    public function useGzip($boolean)
    {

        $this->useGzip = $boolean;
    }

    /**
     * [setUserAgent 设置代理]
     * @param [type] $string [description]
     */
    public function setUserAgent($string)
    {

        $this->user_agent = $string;
    }

    /**
     * [setAuthorization 设置权限]
     * @param [type] $username [description]
     * @param [type] $password [description]
     */
    public function setAuthorization($username, $password)
    {

        $this->username = $username;
        $this->password = $password;
    }

    /**
     * [getCookies 获取cookies]
     * @param  [type] $host [description]
     * @return [type]       [description]
     */
    public function getCookies($host = null)
    {

        if (isset($this->cookies[isset($host) ? $host : $this->host])) {

            return $this->cookies[isset($host) ? $host : $this->host];
        }
        return array();
    }

    /**
     * [setCookies 设置cookies]
     * @param [type]  $array   [description]
     * @param boolean $replace [description]
     */
    public function setCookies($array, $replace = false)
    {

        if ($replace || (!isset($this->cookies[$this->host])) || (!is_array($this->cookies[$this->host]))) {

            $this->cookies[$this->host] = array();
        }

        $this->cookies[$this->host] = array_merge($array, $this->cookies[$this->host]);
    }

    /**
     * [setPersistReferers 设置重定向时，是否保持referer]
     * @param [type] $boolean [description]
     */
    public function setPersistReferers($boolean)
    {

        $this->persistReferers = $boolean;
    }

    /**
     * [setHandleRedirects 设置是否支持重定向]
     * @param [type] $boolean [description]
     */
    public function setHandleRedirects($boolean)
    {

        $this->handleRedirects = $boolean;
    }

    /**
     * [setMaxRedirects 设置重定向总次数]
     * @param [type] $num [description]
     */
    public function setMaxRedirects($num)
    {

        $this->maxRedirects = $num;
    }

    /**
     * [setPersistCookies 设置cookie保持]
     * @param [type] $boolean [description]
     */
    public function setPersistCookies($boolean)
    {

        $this->persistCookies = $boolean;

    }

    /**
     * [send 异步IO，定时器设置，异常回调]
     * @param  callable $callback [description]
     * @return [type]             [description]
     */
    public function send(callable $callback)
    {

        $client = new  \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

        $client->on("connect", function ($cli) {
            $cli->send($this->request);
        });

        $client->on('close', function ($cli) {
        });

        $client->on('error', function ($cli) use ($callback) {

            $cli->close();
            $this->calltime = microtime(true) - $this->calltime;
            call_user_func_array($callback, array('r' => 1, 'key' => $this->key, 'calltime' => $this->calltime, 'error_msg' => 'conncet error'));
        });

        $client->on("receive", function ($cli, $data) use ($callback) {
            /*
                这里的on receivce会被触发多次，耗时和取消定时器都不在这里处理，在packRsp函数里
             */
            call_user_func_array(array($this, 'packRsp'), array('key' => $cli, 'data' => $data));
        });

        $this->callback = $callback;
        if ($client->connect($this->host, $this->port, $this->timeout)) {

            $this->calltime = microtime(true);
            if (floatval(($this->timeout)) > 0) {
                Timer::add($this->key, $this->timeout, $client, $callback, array('r' => 2, 'key' => $this->key, 'calltime' => $this->calltime, 'error_msg' => $this->host . ':' . $this->port . ' timeout'));
            }
        }
    }

    /**
     * [setTimeout 定时]
     * @param [type] $timeout [description]
     */
    public function setTimeout($timeout)
    {

        $this->timeout = $timeout;
    }

    /**
     * [buildQuery description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    private function buildQuery($data)
    {

        if (is_string($data)) {

            $this->postdata = $data;
            return true;
        } else if (is_object($data) || is_array($data)) {

            $this->postdata = http_build_query($data);
            return true;
        } else {
            return false;
        }
    }

    /**
     * [packRsp 组包合包，函数回调]
     * @param  [type] $cli  [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    private function packRsp($cli, $data)
    {

        /*
            1.设置标记位，开始时，解析头部信息
            2.合并boty，两种头部协议
            3.特殊处理 重定向+超时
         */

        $this->buffer .= $data;

        //cookie 保持
        if ($this->persistCookies && isset($this->rspHeaders['set-cookie'])) {

            //TODO support
        }


        if ($this->trunk_length > 0 and strlen($this->buffer) < $this->trunk_length) {
            return;
        }

        if (empty($this->rspHeaders)) {

            $ret = $this->parseHeader($this->buffer);

            if ($ret === false) {
                return;
            } else {
                if ($this->handleRedirects && $this->rspHeaders['status'] >= 300 && $this->rspHeaders['status'] < 400) {
                    //超出最大循环
                    if (++$this->redirectCount >= $this->maxRedirects) {
                        $cli->close();
                        Timer::del($this->key);

                        \SysLog::error(__METHOD__ . " redirectCount over limit ", __CLASS__);

                        call_user_func_array($this->callback, array('r' => 1, 'key' => $this->key, 'calltime' => $this->calltime, 'data' => "redirectCount over limit"));
                        return false;
                    }

                    $location = isset($this->rspHeaders['location']) ? $this->rspHeaders['location'] : '';
                    $location .= isset($this->rspHeaders['uri']) ? $this->rspHeaders['uri'] : '';
                    if (!empty($location)) {

                        \SysLog::debug(__METHOD__ . " redirect location ", __CLASS__);
                        //TODO 尝试client内部重定
                        $url = parse_url($location);
                        $this->host = isset($url['host']) ? $url['host'] : $this->host;
                        $this->body = '';
                        $this->buffer = '';
                        $this->rspHeaders = array();
                        $this->isFinish = false;
                        $cli->close();
                        $http = $this->get($location);
                        $http->send($this->callback);
                        return;
                    } else {
                        $cli->close();
                        Timer::del($this->key);

                        \SysLog::error(__METHOD__ . " redirect location error  ", __CLASS__);

                        call_user_func_array($this->callback, array('r' => 1, 'key' => $this->key, 'calltime' => $this->calltime, 'data' => "redirect location error "));
                        return false;
                    }
                }


                //header + CRLF + body
                if (strlen($this->buffer) > 0) {
                    $parsebody = $this->parseBody();
                }
            }
        } else {
            $parsebody = $this->parseBody();
        }

        if ($parsebody === true and $this->isFinish) {
            $compress_type = empty($this->rspHeaders['content-encoding']) ? '' : $this->rspHeaders['content-encoding'];

            $this->body = self::gz_decode($this->body, $compress_type);

            $data = array('head' => $this->rspHeaders, 'body' => $this->body);
            $cli->close();
            $this->calltime = microtime(true) - $this->calltime;
            Timer::del($this->key);
            call_user_func_array($this->callback, array('r' => 0, 'key' => $this->key, 'calltime' => $this->calltime, 'data' => $data));
        }

    }

    /**
     * [setRequestHeaders 设置请求的headers]
     * @param array $headers [description]
     */
    private function setRequestHeaders($headers = array())
    {

        foreach ($headers as $h_k => $h_v) {

            $this->requestHeaders[$h_k] = $h_v;
        }
    }

    /**
     * [buildRequest 创建request信息]
     * @return [type] [description]
     */
    private function buildRequest()
    {

        $headers = "{$this->method} {$this->path} HTTP/1.1";

        $headerArray = array();
        $headerArray['Host'] = $this->host;
        $headerArray['User-Agent'] = $this->userAgent;
        $headerArray['Accept'] = $this->accept;

        if (isset($this->useGzip)) {

            $headerArray['Accept-encoding'] = $this->acceptEncoding;
        }

        $headerArray['Accept-language'] = $this->acceptLanguage;

        if (isset($this->referer)) {

            $headerArray['Referer'] = $this->referer;
        }

        if (isset($this->cookies[$this->host])) {
            $cookie = '';
            foreach ($this->cookies[$this->host] as $key => $value) {
                $cookie .= "$key=$value; ";
            }
            $headerArray['Cookie'] = $cookie;
        }

        if (isset($this->username) && isset($this->password)) {

            $headerArray['Authorization'] = 'BASIC ' . base64_encode($this->username . ':' . $this->password);
        }

        if ($this->postdata) {
            $headerArray['Content-Type'] = 'application/x-www-form-urlencoded';
            $headerArray['Content-Length'] = strlen($this->postdata);
        }

        //将用户设置的header信息覆盖默认值
        foreach ($this->requestHeaders as $h_k => $h_v) {

            $headerArray[$h_k] = $h_v;
        }

        //拼header
        foreach ($headerArray as $ha_k => $ha_v) {
            $headers .= "\r\n{$ha_k}: {$ha_v}";
        }

        $this->request = $headers . "\r\n\r\n" . $this->postdata;

    }

    /**
     * [parseHeader description]
     * @param  [type] $headerBuf [description]
     * @return [type]            [description]
     */
    private function parseHeader($data)
    {

        /*
            version + status_code + message
         */

        $parts = explode("\r\n\r\n", $data, 2);

        $headParts = explode("\r\n", $parts[0]);
        if (is_string($headParts)) {
            $headParts = explode("\r\n", $headParts);
        }

        if (!is_array($headParts) || !count($headParts)) {

            //TODO header buffer valid
            return false;
        }

        list($this->rspHeaders['protocol'], $this->rspHeaders['status'], $this->rspHeaders['msg']) = explode(' ', $headParts[0], 3);
        unset($headParts[0]);

        foreach ($headParts as $header) {

            $header = trim($header);
            if (empty($header)) {
                continue;
            }

            $h = explode(':', $header, 2);
            $key = trim($h[0]);
            $value = trim($h[1]);
            $this->rspHeaders[strtolower($key)] = $value;
        }

        if (isset($parts[1])) {
            $this->buffer = $parts[1];
        }

        \SysLog::debug(__METHOD__ . " header == " . print_r($this->rspHeaders, true), __CLASS__);
        return true;
    }

    // public function test($r, $k ,$ct, $data){

    // 	echo " r == $r k == $k ct == $ct \n";
    // 	file_put_contents('/tmp/test.html', $data[1]);
    // }
}


