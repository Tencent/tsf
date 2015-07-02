<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/6/8
 * Time: 11:37
 */
class YaafRoute
{
    /**
     * cmd 列表 int
     */
    const CMD_JSAPI_AUTH = 1;
    const CMD_JSAPI_GET_MEDIAID = 2;
    const CMD_JSAPI_GET_UUID = 3;

    /**
     * @var array
     * cmd 管理数组
     */
    static $yaaf_cmd = array(
        self::CMD_JSAPI_AUTH,
        self::CMD_JSAPI_GET_MEDIAID,
        self::CMD_JSAPI_GET_UUID,
    );
    /**
     * 路由映射管理
     * @var array
     * 'cmd' => array('controller'=>'Group','action'=>'Add');
     */
    protected static $route = array(
        self::CMD_JSAPI_AUTH => array(
            'controller' => 'JsSdk',
            'action' => 'Auth',
        ),
        self::CMD_JSAPI_GET_MEDIAID => array(
            'controller' => 'JsSdk',
            'action' => 'GetMediaId',
        ),
        self::CMD_JSAPI_GET_UUID => array(
            'controller' => 'JsSdk',
            'action' => 'GetUuid',
        ),
    );


    public static function getRoute($cmd, $verb = null)
    {
        $mvc = array();
        if (array_key_exists((int)$cmd, self::$route)) {
            $mvc = self::$route[$cmd];
        } else {
            //先粗糙的这么搞吧
            return false;
        }
        return $mvc;
    }


}