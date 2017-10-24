<?php

/**
 * Tencent is pleased to support the open source community by making TSF Solution available.
 * Copyright (C) 2017 THL A29 Limited, a Tencent company. All rights reserved.
 * Licensed under the BSD 3-Clause License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * https://opensource.org/licenses/BSD-3-Clause
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 */
class HttpHelper
{


    //解析成功
    const HTTP_OK = 0x00;
    //请求方式错误
    const HTTP_ERROR_METHOD = 0x01;
    //请求uri错误
    const HTTP_ERROR_URI = 0x02;

    /**
     * 处理request对象
     * @param req swoole http server 获得的request对象
     */
    public static function httpReqHandle($req)
    {

        $method = $req->server['request_method'];
        $uri = $req->server['request_uri'];
        //正则匹配的路由 支持restful 提供给深度用户使用
        // $appRoute = HttpRoute::urlrouter_rewrite($uri,$method);
        // explode 解析类似于  controller/action类型的url

        //默认会解析到default/index
        $mvcArr = explode('/', $uri);
        $appRoute['controller'] = isset($mvcArr[1]) ? $mvcArr[1] : 'default';
        $appRoute['action'] = isset($mvcArr[2]) ? $mvcArr[2] : 'index';

        SysLog::info(__METHOD__ . print_r($appRoute, true), __CLASS__);
        if (!$appRoute) {
            return array('r' => self::HTTP_ERROR_URI);
        }

        return array('r' => self::HTTP_OK,
            'route' => $appRoute,
            'request' => array('uri' => $uri,
                'header' => $req->header,
                'get' => array_merge((array)(isset($req->get) ? $req->get : array()), (array)$appRoute['get']),
                'post' => (isset($req->post)) ? $req->post : '',
                'files' => isset($req->files) ? $req->files : '',
                'cookie' => isset($req->cookie) ? $req->cookie : '',
                'rawcontent' => $req->rawContent(),
                'method' => $method,
            ),
        );
    }


}
