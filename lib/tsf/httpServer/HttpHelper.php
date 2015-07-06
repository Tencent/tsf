<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jimmyszhou
 * Date: 15-3-5
 * Time: 下午1:29
 * To change this template use File | Settings | File Templates.
 * uri => cmd =>
 */
class HttpHelper {


    //解析成功
    const HTTP_OK = 0x00;
    //请求方式错误
    const HTTP_ERROR_METHOD = 0x01;
    //请求uri错误
    const HTTP_ERROR_URI = 0x02;


    public static function getRoute($uri,$verb=null){
        return array('r'=>0,'controller'=>'Mark','action'=>'Marktest');

    }

    /**
     * 处理request对象
     * @param req swoole http server 获得的request对象
     */
    public static function httpReqHandle($req){

        $method = $req -> server['request_method'];
        $uri = $req -> server['request_uri'];
        //路由
        $appRoute = HttpRoute::urlrouter_rewrite($uri,$method);
        SysLog::info(__METHOD__.print_r($appRoute,true),__CLASS__);
        if(!$appRoute){
            return array('r' => self::HTTP_ERROR_URI);
        }


        //  array_merge((array)($req -> get),(array)$appRoute['get']);

        return array('r' => self::HTTP_OK,
            'route' => $appRoute,
            //如果同时有post 和 file  swoole 会自动解析为post 和rawContent还在 音视频需要原样转发
            'request' => array('uri' => $uri,
                'header' => $req ->header,
                'get' => array_merge((array)(isset($req -> get)?$req -> get:array()),(array)$appRoute['get']),
                'post' => (isset($req -> post))? $req -> post : $req -> rawContent(),  //暂定这样 留待验证
                'files' => isset($req -> files)?$req -> files:'',
                'cookie' => isset($req -> cookie)?$req -> cookie:'',
                'rawcontent' => $req -> rawContent(),
                'method' => $method,


            ),
        );
    }



}
