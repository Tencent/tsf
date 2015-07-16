<?php
/**
 * Created by PhpStorm, defined by wallyzhang.
 * User: markyuan
 * Date: 14-7-16
 * Time: 下午10:23
 * Version: 1.0
 */

class MPConst {


    const NUM = 684767;     //常量

    public static function getDBConf()  //一些配置
    {
        return array(
            'ip' => '0.0.0.0',
            'port' => '555',

        );
    }

} 