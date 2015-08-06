<?php

/**
 * Created by JetBrains PhpStorm.
 * User: jimmyszhou
 * Date: 15-3-5
 * Time: 下午1:29
 * To change this template use File | Settings | File Templates.
 * uri => cmd =>
 */
class HttpRoute
{

    public static function getRoute($uri, $verb = null)
    {
        return array('r' => 0, 'controller' => 'Mark', 'action' => 'Marktest');

    }


}