<?php

/**
 * Created by JetBrains PhpStorm.
 * User: jimmyszhou
 * Date: 15-3-5
 * Time: 上午11:15
 * To change this template use File | Settings | File Templates.
 */
class YaafHelper
{
    //解析成功
    const YAAF_OK = 0x00;
    //请求CMD错误
    const YAAF_ERROR_CMD = 0x02;

    /**
     * 处理request对象
     * @param req swoole yaaf server 获得的request对象
     */
    public static function yaafReqHandle($req)
    {
        $cmd = $req['reqHead']['cmd'];
        //路由
        $appRoute = YaafRoute::getRoute($cmd);
        SysLog::info(__METHOD__ . print_r($appRoute, true), __CLASS__);
        if (!$appRoute) {
            return array('r' => self::YAAF_ERROR_CMD);
        }

        //这里考虑以后根据appName路由到后端svr yaafUdpServ作为代理 分发流量


        return array('r' => self::YAAF_OK,
            'route' => $appRoute,
            'request' => array(
                'reqHead' => $req['reqHead'],
                'reqBody' => $req['reqBody'],
            ),
        );
    }

    public static function httpCgiProxyHandle($req)
    {
        //$appRoute = HttpRoute::getRoute($uri);
        /*
        if(!$appRoute){
            return array('r' => self::HTTP_ERROR_URI);
        }
        */
        /*
        return array('r' => self::HTTP_OK,
            'route' => $appRoute,
            'request' => array('uri' => $uri,
                'get' => $req -> get,
                'post' => $req -> post ? $req -> post : $req -> rawContent(),
            ),
        );*/
    }

    /**
     * @param $rsp swoole http server 获得的response对象
     */
    public static function httpRspHandle($rsp)
    {
        return $rsp;
    }
}
