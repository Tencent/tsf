<?php

/**
 * Created by PhpStorm.
 * User: yuanyizhi
 * Date: 15/6/26
 * Time: 上午12:11
 */
class Filter
{

    //初始化
    public function init()
    {

    }

    //前面filter
    static public function preFilter($data)
    {
        return true;
    }

    //后面filter
    static public function postFilter($data)
    {
    }

}