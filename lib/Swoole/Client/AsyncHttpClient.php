<?php
namespace Swoole\Client;

class AsyncHttpClient
{
    const EOF = "\r\n";
    const PORT = 80;

    protected $timeout = 5000;  //以毫秒为单位 超时返回时间
    protected $connectTmeout = 2;  //以秒为单位  connect的时间
    public $isTimeOut=false; //是否是超时

    public $url;
    public $uri;
    public $reqHeader;
    public $userFlag;
    public $tmpStatusCode;


    protected $cli;
    protected $buffer = '';
    protected $nparse = 0;
    protected $isError = false;
    protected $isFinish = false;
    protected $status = array();
    protected $respHeader = array();
    protected $body = '';
    protected $trunk_length = 0;
    protected $userAgent = 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36';
    protected $onReadyCallback;     //保存回调obj
    protected $onReadyCallbackName; //保存回调类方程

    private  $clientindex=0;

    //添加新的
    protected $cookies=array();
    protected $max_redirects = 3;
    protected $auto_redirects = true;
    protected $redirect_count = 0;  //记录已经重定向过的次数
    protected $use_gzip = true;
    protected $method ;
    protected $username ;
    protected $password ;
    protected $postdata='';
    protected $querystring='';
    protected $useProxy=false;
    protected $proxyIp='';
    protected $proxyPort='';
    protected $contenttype='';

    //用来转换header
    function parseHeader($data)
    {
        $parts = explode("\r\n\r\n", $data, 2);

        // parts[0] = HTTP头;
        // parts[1] = HTTP主体，GET请求没有body
        $headerLines = explode("\r\n", $parts[0]);

        // HTTP协议头,方法，路径，协议[RFC-2616 5.1]
        list($status['method'], $status['uri'], $status['protocol']) = explode(' ', $headerLines[0], 3);
        //错误的HTTP请求
        if (empty($status['method']) or empty($status['uri']) or empty($status['protocol']))
        {
            return false;
        }
     //   unset($headerLines[0]);
        //解析Header
        $this->respHeader =  \Swoole\Http\Parser::parseHeaderLine($headerLines);
        $this->status = $status;
        if (isset($parts[1]))
        {
            $this->buffer = $parts[1];
        }
        return true;
    }

    function errorLog($msg)
    {
       // error_log(__LINE__.$msg . PHP_EOL, 3, '/tmp/AsyncHttpClient.log');
        \SysLog::info(print_r($msg,true),'AsyncHttpClient');
    }


    function errorLogTime($msg)
    {
        //error_log(__LINE__.$msg . PHP_EOL, 3, '/tmp/AsyncHttpClientTime.log');
        \SysLog::info(print_r($msg,true),'AsyncHttpClient');
    }

    //用来转换body
    function parseBody()
    {
        //解析trunk
        if (isset($this->respHeader['Transfer-Encoding']) and $this->respHeader['Transfer-Encoding'] == 'chunked')
        {
            while(1)
            {
                if ($this->trunk_length == 0)
                {
                    $_len = strstr($this->buffer, "\r\n", true);
                    if ($_len === false)
                    {
                        $this->errorLog( "Trunk: length error, $_len");
                        return false;
                    }
                    $length = hexdec($_len);
                    if ($length == 0)
                    {
                        $this->isFinish = true;
                        return true;
                    }
                    $this->trunk_length = $length;
                    $this->buffer = substr($this->buffer, strlen($_len) + 2);
                }
                else
                {
                    //数据量不足，需要等待数据
                    if (strlen($this->buffer) < $this->trunk_length)
                    {
                        return false;
                    }
                    $this->body .= substr($this->buffer, 0, $this->trunk_length);
                    $this->buffer = substr($this->buffer, $this->trunk_length + 2);
                    $this->trunk_length = 0;
                }
            }
            return false;
        }
        //普通的Content-Length约定
        else
        {
            if (strlen($this->buffer) < $this->respHeader['Content-Length'])
            {
                return false;
            }
            else
            {
                $this->body = $this->buffer;
                $this->isFinish = true;
                return true;
            }
        }
    }

    static function gz_decode($data, $type = 'gzip')
    {
        if ($type == 'gzip')
        {
            return gzdecode($data);
        }
        elseif ($type == 'deflate')
        {
            return gzinflate($data);
        }
        elseif($type == 'compress')
        {
            return gzinflate(substr($data,2,-4));
        }
        else
        {
            return $data;
        }
    }

    function setCookie($cookies)
    {
        $this->cookies = $cookies;
    }

    function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }

    function setUserProxy($proxyIp,$proxyPort)
    {
        $this->useProxy=true;
        $this->proxyIp = $proxyIp;
        $this->proxyPort = $proxyPort;
    }

    //设置认证
    function setAuthorization($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }

    //设置超时时间
    function setTimeout($timeout) {
        $this->timeout = $timeout;
    }

    //设置联接的超时时间
    function setConnectTimeout($timeout) {
        $this->connectTmeout = $timeout;
    }

    //设置了头部
    function setHeader($k, $v)
    {
        $this->reqHeader[$k] = $v;
    }

    function setRedirectCounts($last_redirect_count)
    {
        $this->redirect_count = $last_redirect_count;
    }

    function  generateHeader()
    {

        $header = "{$this->method} ".($this->path).(empty($this->querystring)?'':'?'.$this->querystring).' HTTP/1.1'. self::EOF;    //
        $header .= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8' . self::EOF;
        $header .= 'Accept-Encoding: gzip,deflate,sdch' . self::EOF;
        $header .= 'Accept-Language: zh-CN,zh;q=0.8,en;q=0.6,zh-TW;q=0.4,ja;q=0.2' . self::EOF;
        if($this->uri['port']==80){
            $header .= 'Host: '.$this->uri['host']. self::EOF;
        }else{
            $header .= 'Host: '.$this->uri['host'].':'.$this->uri['port']. self::EOF;
        };

//        $header .= 'RA-Sid: 2A784AF7-20140212-113827-085a9c-c4de6e' . self::EOF;
//        $header .= 'RA-Ver: 2.2.1' . self::EOF;
        $header .= 'Referer: http://'.$this->uri['host'].'/' . self::EOF;
        //添加认证
        if ($this->username && $this->password) {
            $header .='Authorization: BASIC '.base64_encode($this->username.':'.$this->password). self::EOF;
        }
        // Cookies
        if ($this->cookies) {
            $cookie = 'Cookie: ';
            foreach ($this->cookies as $key => $value) {
                $cookie .= "$key=$value; ";
            }
            $header .= $cookie. self::EOF;;
        }
        $header .= $this->userAgent . self::EOF;
        if (!empty($this->reqHeader))
        {
            foreach ($this->reqHeader as $k => $v)
            {
                $header .= $k . ': ' . $v . self::EOF;
            }
        };
        //添加post
        if ($this->postdata) {
            if(isset($this->reqHeader['Content-Type'])){    //如果设置了content-type 不需要再拼
             //   $header .= 'Content-Type: application/x-www-form-urlencoded'. self::EOF;
            }else{
                $header .= 'Content-Type: application/x-www-form-urlencoded'. self::EOF;
            }

            $header .= 'Content-Length: '.strlen($this->postdata). self::EOF;
        }
        $header .="\r\n".$this->postdata;
        return $header;
    }


    // connect 用于拼接头部,目前只有get方法
    function onConnect(\swoole_client $cli)
    {
        $this->errorLogTime('begin to onConnect and time is '.date('H:i:s').' and time out is '.$this->timeout);
        swoole_timer_after($this->timeout, array($this,'closeConnect'));  //添加超时
        $header=$this->generateHeader();
        $this->errorLog($header);
        $cli->send($header);
    }



    //设定回调函数，支持传入对象
    function  onReady($func,$obj=null)
    {

        $this->errorLog('on ready ');
        if(isset($obj))
        {
            if ( is_callable(array($obj,$func)))  //如果设置了obj则 保存
            {
                $this->onReadyCallback=$obj;
                $this->onReadyCallbackName=$func;
            }
            else
            {
                throw new \Exception(__CLASS__.": function is not callable.");
            }
        }else
        {
            if ( is_callable($func))
            {
                $this->onReadyCallback = $func;
            }
            else
            {
                throw new \Exception(__CLASS__.": function is not callable.");
            }
        };
    }

    function onReceive($cli, $data)
    {
        $this->errorLog(__LINE__.'on onReceive '.print_r($data,true));
        $this->buffer .= $data;
        if ($this->trunk_length > 0 and strlen($this->buffer) < $this->trunk_length)
        {
            return;
        }
        if (empty($this->respHeader))
        {
            $ret = $this->parseHeader($this->buffer) ;
            if ($ret === false)
            {
                return;
            }
            else
            {
                //header + CRLF + body     如果buffer大于0
                if (strlen($this->buffer) > 0  ||  isset($this->respHeader['Content-Length']))
                {
                    goto parse_body;
                }else{      //如果 ==0  说明没有body  直接 close
                    $cli->close();
                }
            }
        }
        else
        {
            parse_body:
            if ($this->parseBody() === true and $this->isFinish)
            {
                //收包完成后，调用回调函数
                $compress_type = empty($this->respHeader['Content-Encoding'])?'':$this->respHeader['Content-Encoding'];
                $this->body = self::gz_decode($this->body, $compress_type);
                //如果完成了 直接
                $cli->close();

            }
        }
    }

    function closeConnect()  //注册了超时之后，一定会执行
    {

        $this->errorLogTime('begin to closeConnect and time is '.date('H:i:s').' and time out is '.$this->timeout);
        if($this->cli->isConnected()){ //如果还在联接，说明超时了
        //    echo "begin to closeConnect and to initial.\n";
            $this->errorLog('begin to closeConnect and to initial ');
            $this->buffer = '';
            $this->isFinish = false;
            $this->status = array();
            $this->respHeader = array();
            $this->body = '';
            $this->trunk_length = 0;
            $this->clientindex=0;
            $this->redirect_count = 0;  //记录已经重定向过的次数
            $this->isTimeOut=true;   //标示已经超时了
            $this->cli->close();
        }else{       //已经不连接了 说明

        }



    }


    function onError($cli)  //报错的话，就关闭
    {
        $this->errorLog('Connect to server failed. ');
        $this->closeConnect();
    }
    function nest($fd,$body,$head)
    {
        $this->body=$body;
        $this->respHeader=$head;
        $response=array();
        if($this->isTimeOut){ //如果是因为超时了  则code设置为500
            $response['code']=500;
            $this->isTimeOut=false;
        }else{
            $response['code']=$this->status['uri'];
        };
        $response['header']=$this->respHeader;
        $response['body']=$this->body;
        if( ! is_callable($this->onReadyCallback))
        {
            if(isset($this->onReadyCallbackName)){  //如果设置了的话
                call_user_func(array($this->onReadyCallback,$this->onReadyCallbackName), $this,$response);
            }else{
                call_user_func(array($this->onReadyCallback,'nest'), $this,$response);
            }
        }else
        {
            call_user_func($this->onReadyCallback, $this,$response);
        }
    }

    function onClose($cli)
    {
        //如果容许跳转且小于最大跳转次数
        if( ($this->status['uri'] == '302' || $this->status['uri'] == '301') &&  ($this->auto_redirects )&& ($this->redirect_count<$this->max_redirects))
        {
            $jumpUrl=$this->respHeader['Location'];
            $newclass=new AsyncHttpClient($jumpUrl,'GET');
            $newclass->redirect_count=(($this->redirect_count)+1);
            $newclass->auto_redirects=true;
            if( $this->useProxy==true)
            {
                $newclass->setUserProxy($this->proxyIp,$this->proxyPort);
            }
            $newclass->execute();
            $newclass->onReady('nest',$this);
        }else{ //如果是正常的情况 无跳转 直接调用
            $response=array();
            if($this->isTimeOut){ //如果是因为超时了  则code设置为500
                $response['code']=500;
                $this->isTimeOut=false;
            }else{
                $response['code']=$this->status['uri'];
            };
            $response['header']=$this->respHeader;
            $response['body']=$this->body;
            if( ! is_callable($this->onReadyCallback))
            {
                if(isset($this->onReadyCallbackName)){  //如果设置了的话
                    call_user_func(array($this->onReadyCallback,$this->onReadyCallbackName), $this,$response);
                }else{
                    call_user_func(array($this->onReadyCallback,'nest'), $this,$response);
                }

            }else
            {
                call_user_func($this->onReadyCallback, $this, $response);
            }
        }

    }

    function execute()
    {
        $cli = new \swoole_client(SWOOLE_TCP, SWOOLE_SOCK_ASYNC);
        $this->cli = $cli;
        $this->cli->index=$this->clientindex;
        $this->clientindex++;
        $cli->on('connect', array($this, 'onConnect'));
        $cli->on('error', array($this, 'onError'));
        $cli->on('Receive', array($this, 'onReceive'));
        $cli->on('close', array($this, 'onClose'));
        if($this->useProxy)
        {
            $this->errorLog('use proxy ip '.$this->proxyIp.' and port is '.$this->proxyPort);
            $cli->connect($this->proxyIp, $this->proxyPort, $this->connectTmeout);


        }else
        {
            $this->errorLog('no proxy ip '.$this->uri['host'].' and port is '.$this->uri['port']);
            $cli->connect($this->uri['host'], $this->uri['port'], $this->connectTmeout);
        }
    }

    function quickPost($url, $data) {

        $this->querystring=$this->uri['query'];
        $querystring = '';
        if (is_array($data)) {   //如果是数组
            // Change data in to postable data
            foreach ($data as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $val2) {
                        $querystring .= urlencode($key).'='.urlencode($val2).'&';
                    }
                } else {
                    $querystring .= urlencode($key).'='.urlencode($val).'&';
                }
            }
            $querystring = substr($querystring, 0, -1); // Eliminate unnecessary &
        } else {                   //如果不是数组，则内容直接推过去，自己定义头部
            $querystring = $data;
        };
        $this->postdata=$querystring;
    }

    function quickGet($url, $data) {
        $this->querystring=$this->uri['query'];
    }




    //默认为get方法，空数据
    function __construct($url,$method='GET',$data=array(),$timeout=5000) //默认5s超时
    {
        $this->timeout = $timeout;
        $this->url = $url;
        $this->uri = parse_url($this->url);
        $this->errorLog(__LINE__."url is ".$url." and construct is ".print_r($this->uri,true));
        $this->method=$method;
        $this->path = isset( $this->uri['path']) ? ($this->uri['path']) : '/';
        if (empty($this->uri['port']))
        {
            $this->uri['port'] = self::PORT;
        };
        if($method=='POST')
        {
            $this->quickPost($url,$data);
        }else{
            $this->quickGet($url,$data);
        }
    }


}
