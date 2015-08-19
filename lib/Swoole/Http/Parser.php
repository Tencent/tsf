<?php
namespace Swoole\Http;

use Swoole;

/**
 * Class ExtParser
 * 使用pecl_http扩展
 * @package Swoole\Http
 */
class Parser
{
    const HTTP_EOF = "\r\n\r\n";

    protected $buffer;
    protected $header;
    protected $isError;

    /**
     * 头部解析
     * @param $data
     * @return array
     */
    static function parseHeader($data)
    {
        $header = array();
        $header[0] = array();
        $meta = &$header[0];
        $parts = explode("\r\n\r\n", $data, 2);

        // parts[0] = HTTP头;
        // parts[1] = HTTP主体，GET请求没有body
        $headerLines = explode("\r\n", $parts[0]);

        // HTTP协议头,方法，路径，协议[RFC-2616 5.1]
        list($meta['method'], $meta['uri'], $meta['protocol']) = explode(' ', $headerLines[0], 3);

        //错误的HTTP请求
        if (empty($meta['method']) or empty($meta['uri']) or empty($meta['protocol'])) {
            return false;
        }
        unset($headerLines[0]);
        //解析Header
        $header = array_merge($header, self::parseHeaderLine($headerLines));
        return $header;
    }

    /**
     * 传入一个字符串或者数组
     * @param $headerLines string/array
     * @return array
     */
    static function parseHeaderLine($headerLines)
    {
        if (is_string($headerLines)) {
            $headerLines = explode("\r\n", $headerLines);
        }
        $header = array();
        foreach ($headerLines as $_h) {
            $_h = trim($_h);
            if (empty($_h)) continue;
            $_r = explode(':', $_h, 2);
            $key = $_r[0];
            $value = isset($_r[1]) ? $_r[1] : '';
            $header[trim($key)] = trim($value);
        }
        return $header;
    }

    static function parseParams($str)
    {
        $params = array();
        $blocks = explode(";", $str);
        foreach ($blocks as $b) {
            $_r = explode("=", $b, 2);
            if (count($_r) == 2) {
                list ($key, $value) = $_r;
                $params[trim($key)] = trim($value, "\r\n \t\"");
            } else {
                $params[$_r[0]] = '';
            }
        }
        return $params;
    }

    function parseBody($request)
    {
        $cd = strstr($request->head['Content-Type'], 'boundary');
        if (isset($request->head['Content-Type']) and $cd !== false) {
            $this->parseFormData($request, $cd);
        } else {
            parse_str($request->body, $request->post);
        }
    }

    /**
     * 解析Cookies
     * @param $request \Swoole\Request
     */
    function parseCookie($request)
    {
        $request->cookie = self::parseParams($request->head['Cookie']);
    }

    /**
     * 解析form_data格式文件
     * @param $part
     * @param $request
     * @param $cd
     * @return unknown_type
     */
    static function parseFormData($request, $cd)
    {
        $cd = '--' . str_replace('boundary=', '', $cd);
        $form = explode($cd, rtrim($request->body, "-")); //去掉末尾的--
        foreach ($form as $f) {
            if ($f === '') continue;
            $parts = explode("\r\n\r\n", trim($f));
            $head = self::parseHeaderLine($parts[0]);
            if (!isset($head['Content-Disposition'])) continue;
            $meta = self::parseParams($head['Content-Disposition']);
            //filename字段表示它是一个文件
            if (!isset($meta['filename'])) {
                if (count($parts) < 2) $parts[1] = "";
                //支持checkbox
                if (substr($meta['name'], -2) === '[]') $request->post[substr($meta['name'], 0, -2)][] = trim($parts[1]);
                else $request->post[$meta['name']] = trim($parts[1], "\r\n");
            } else {
                $file = trim($parts[1]);
                $tmp_file = tempnam('/tmp', 'sw');
                file_put_contents($tmp_file, $file);
                if (!isset($meta['name'])) $meta['name'] = 'file';
                $request->file[$meta['name']] = array('name' => $meta['filename'],
                    'type' => $head['Content-Type'],
                    'size' => strlen($file),
                    'error' => UPLOAD_ERR_OK,
                    'tmp_name' => $tmp_file);
            }
        }
    }

    /**
     * 头部http协议
     * @param $data
     * @return array
     */
    function parse($data)
    {
        $_header = strstr($data, self::HTTP_EOF, true);
        if ($_header === false) {
            $this->buffer = $data;
        }
        $header = self::parseHeader($_header);
        if ($header === false) {
            $this->isError = true;
        }
        $this->header = $header;
        return $header;
    }
}