<?php
/**
 * Created by JetBrains PhpStorm.
 * User: chalesi
 * Date: 14-2-24
 * Time: 上午11:21
 * To change this template use File | Settings | File Templates.
 */
namespace Swoole\Network\Protocol;

use Swoole;

class HttpServer extends Swoole\Network\Protocol implements Swoole\Server\Protocol
{
    const SOFTWARE = "Swoole-HttpServer";

    protected $swoole_server;
    protected $buffer_header = array();
    protected $buffer_maxlen = 65535; //最大POST尺寸，超过将写文件
    protected $currentResponse;

    const DATE_FORMAT_HTTP = 'D, d-M-Y H:i:s T';

    const HTTP_EOF = "\r\n\r\n";
    const HTTP_HEAD_MAXLEN = 2048; //http头最大长度不得超过2k

    const ST_FINISH = 1; //完成，进入处理流程
    const ST_WAIT = 2; //等待数据
    const ST_ERROR = 3; //错误，丢弃此包

    function __construct()
    {
        $this->parser = new Swoole\Http\Parser;
    }

    function checkHeader($client_id, $http_data)
    {
        //新的连接
        if (!isset($this->requests[$client_id])) {
            if (!empty($this->buffer_header[$client_id])) {
                $http_data = $this->buffer_header[$client_id] . $http_data;
            }
            //HTTP结束符
            $ret = strpos($http_data, self::HTTP_EOF);
            //没有找到EOF，继续等待数据
            if ($ret === false) {
                return false;
            } else {
                $this->buffer_header[$client_id] = '';
                $request = new Swoole\Http\Request;
                //GET没有body
                list($header, $request->body) = explode(self::HTTP_EOF, $http_data, 2);
                $request->head = $this->parser->parseHeader($header);
                //使用head[0]保存额外的信息
                $request->meta = $request->head[0];
                unset($request->head[0]);
                //保存请求
                $this->requests[$client_id] = $request;
                //解析失败
                if ($request->head == false) {
                    $this->log("parseHeader fail. header=" . $header);
                    return false;
                }
            }
        } //POST请求需要合并数据
        else {
            $request = $this->requests[$client_id];
            $request->body .= $http_data;
        }
        return $request;
    }

    function checkPost($request)
    {
        if (isset($request->head['Content-Length'])) {
            //超过最大尺寸
            if (intval($request->head['Content-Length']) > $this->buffer_maxlen) {
                $this->log("checkPost fail. post_data is too long.");
                return self::ST_ERROR;
            }
            //不完整，继续等待数据
            if (intval($request->head['Content-Length']) > strlen($request->body)) {
                return self::ST_WAIT;
            } //长度正确
            else {
                return self::ST_FINISH;
            }
        }
        $this->log("checkPost fail. Not have Content-Length.");
        //POST请求没有Content-Length，丢弃此请求
        return self::ST_ERROR;
    }

    function checkData($client_id, $http_data)
    {
        if (isset($this->buffer_header[$client_id])) {
            $http_data = $this->buffer_header[$client_id] . $http_data;
        }
        //检测头
        $request = $this->checkHeader($client_id, $http_data);
        //错误的http头
        if ($request === false) {
            $this->buffer_header[$client_id] = $http_data;
            //超过最大HTTP头限制了
            if (strlen($http_data) > self::HTTP_HEAD_MAXLEN) {
                $this->log("http header is too long.");
                return self::ST_ERROR;
            } else {
                $this->log("wait request data. fd={$client_id}");
                return self::ST_WAIT;
            }
        }
        //POST请求需要检测body是否完整
        if ($request->meta['method'] == 'POST') {
            return $this->checkPost($request);
        } //GET请求直接进入处理流程
        else {
            return self::ST_FINISH;
        }
    }

    /**
     * 接收到数据
     * @param $serv \swoole_server
     * @param $client_id
     * @param $from_id
     * @param $data
     * @return null
     */
    function onReceive($serv, $client_id, $from_id, $data)
    {
        //检测request data完整性
        $ret = $this->checkData($client_id, $data);
        switch ($ret) {
            //错误的请求
            case self::ST_ERROR;
                $this->server->close($client_id);
                return;
            //请求不完整，继续等待
            case self::ST_WAIT:
                return;
            default:
                break;
        }
        //完整的请求 开始处理
        $request = $this->requests[$client_id];
        $info = $serv->connection_info($client_id);
        $request->remote_ip = $info['remote_ip'];
        $_SERVER['SWOOLE_CONNECTION_INFO'] = $info;

        $request = $this->parseRequest($request);
        $request->fd = $client_id;
        $request->setGlobal();
        $dataRet = $this->onRequest($request);
        if (!is_null($dataRet)) // 有返回直接response
        {
            $response = new Swoole\Http\Response;
            //处理请求，产生response对象
            $response->body = $dataRet;
            //发送response
            $this->response($request, $response);
        }
    }

    function afterResponse(Swoole\Http\Request $request, Swoole\Http\Response $response)
    {
        if (!$this->keepalive or $response->head['Connection'] == 'close') {
            $this->server->close($request->fd);
        }
        $request->unsetGlobal();
        //清空request缓存区
        unset($this->requests[$request->fd]);
        unset($request);
        unset($response);
    }

    /**
     * 解析请求
     * @param $request Swoole\Http\Request
     * @return null
     */
    function parseRequest($request)
    {
        $url_info = parse_url($request->meta['uri']);
        $request->time = time();
        $request->meta['path'] = $url_info['path'];
        if (isset($url_info['fragment'])) $request->meta['fragment'] = $url_info['fragment'];
        if (isset($url_info['query'])) {
            parse_str($url_info['query'], $request->get);
        }
        //POST请求,有http body
        if ($request->meta['method'] === 'POST') {
            $this->parser->parseBody($request);
        }
        //解析Cookies
        if (!empty($request->head['Cookie'])) {
            $this->parser->parseCookie($request);
        }
        return $request;
    }

    /**
     * 发送响应
     * @param $request Swoole\Http\Request
     * @param $response Swoole\Http\Response
     * @return bool
     */
    function response(Swoole\Http\Request $request, Swoole\Http\Response $response)
    {
        if (!isset($response->head['Date'])) {
            $response->head['Date'] = gmdate("D, d M Y H:i:s T");
        }
        if (!isset($response->head['Connection'])) {
            //keepalive
            if ($this->keepalive and (isset($request->head['Connection']) and strtolower($request->head['Connection']) == 'keep-alive')) {
                $response->head['KeepAlive'] = 'on';
                $response->head['Connection'] = 'keep-alive';
            } else {
                $response->head['KeepAlive'] = 'off';
                $response->head['Connection'] = 'close';
            }
        }
        //过期命中
        if ($this->expire and $response->http_status == 304) {
            $out = $response->getHeader();
            return $this->server->send($request->fd, $out);
        }
        //压缩
        if ($this->gzip) {
            $response->head['Content-Encoding'] = 'deflate';
            $response->body = gzdeflate($response->body, $this->config['server']['gzip_level']);
        }
        $out = $response->getHeader() . $response->body;
        $ret = $this->server->send($request->fd, $out);
        $this->afterResponse($request, $response);
        return $ret;
    }

    /**
     * 发生了http错误
     * @param                 $code
     * @param Swoole\Http\Response $response
     * @param string $content
     */
    function httpError($code, Swoole\Http\Response $response, $content = '')
    {
        $response->send_http_status($code);
        $response->head['Content-Type'] = 'text/html';
        $response->body = Swoole\Error::info(Swoole\Http\Response::$HTTP_HEADERS[$code], "<p>$content</p><hr><address>" . self::SOFTWARE . " at {$this->server->host} Port {$this->server->port}</address>");
    }

    /**
     * 捕获错误
     */
    function onError()
    {
        $error = error_get_last();
        if (!isset($error['type'])) return;
        switch ($error['type']) {
            case E_ERROR :
            case E_PARSE :
            case E_DEPRECATED:
            case E_CORE_ERROR :
            case E_COMPILE_ERROR :
                break;
            default:
                return;
        }
        $errorMsg = "{$error['message']} ({$error['file']}:{$error['line']})";
        $message = self::SOFTWARE . " Application Error: " . $errorMsg;
        if (empty($this->currentResponse)) {
            $this->currentResponse = new Swoole\Http\Response();
        }
        $this->currentResponse->send_http_status(500);
        $this->currentResponse->body = $message;
        $this->response($this->currentRequest, $this->currentResponse);
    }

    /**
     * 处理请求
     * @param $request
     * @return null
     */
    function onRequest(Swoole\Http\Request $request)
    {
    }

    public function onStart($serv, $workerId)
    {
    }

    public function onShutdown($serv, $workerId)
    {
    }

    public function onConnect($server, $fd, $fromId)
    {
    }

    public function onTask($serv, $taskId, $fromId, $data)
    {

    }

    public function onFinish($serv, $taskId, $data)
    {

    }

    public function onTimer($serv, $interval)
    {

    }

    /**
     * @param \swoole_server $serv
     * @param $fd
     * @param $from_id
     */
    function onClose($serv, $fd, $from_id)
    {
    }
}