<?php

/**
 * Created by JetBrains PhpStorm.
 * User: jimmyszhou
 * Date: 15-3-5
 * Time: 下午1:29
 * To change this template use File | Settings | File Templates.
 * uri => cmd =>
 */
class HttpRoute extends Route
{

    public static function getRoute($uri, $verb = null)
    {
        return array('r' => 0, 'controller' => 'Mark', 'action' => 'Marktest');

    }


    public static function urlrouter_rewrite(&$uri, $verb = null)  //默认为
    {
        //读取配置文件
        $rewrite = Config::getConfig('httpServer', 'Rewrite');
        if (empty($rewrite) or !is_array($rewrite)) {
            return false;
        }
        $match = array();
        $uri_for_regx = $uri;

        foreach ($rewrite as $rule) {
            // 'regx' => '^/(<controller>\w+)/(<action>\w+)\.html$',  //这种是默认格式 其余都转到get里面
            //如果设置了规则，并且传进来的不同 则pass 如果未设置，则不需要考虑
            if ((!empty($rule['verb'])) && ($verb != $rule['verb'])) {
                continue;
            }
            //error_log(__LINE__."  \r\n",3,'/tmp/router.log');
            $mvc = $rule['mvc'];
            $mvcArr = explode('/', $mvc);
            if (count($mvcArr) < 2) {  //如果小于2 则返回false
                return false;
            }
            $mvc = array();
            $mvc['controller'] = $mvcArr[0];  //获取了controller 和 action
            $mvc['action'] = $mvcArr[1];
            $tmp = array();
            if (preg_match_all('/<\w+>/', $rule['regx'], $match)) {
                foreach ($match[0] as $k => $v) {  //赋值到get参数内 按照顺序筛选出来 赋值出来key值
                    $tmp[] = trim($v, '<>');
                }
            };

            $regx = preg_replace('/<\w+>/', '', $rule['regx']); //获得实际的正则表达式
            if (preg_match('#' . $regx . '#i', $uri_for_regx, $match)) {
                //如果设置了mvc 则走指定的controller
                foreach ($tmp as $k => $v) {
                    if ($v == 'controller') {
                        $mvc['controller'] = ucwords($match[$k + 1]);  //获取了controller 和 action
                        continue;
                    }
                    if ($v == 'action') {
                        $mvc['action'] = ucwords($match[$k + 1]);
                        continue;
                    }

                    if ($v == 's_action') {
                        $mvc['action'] .= ucwords($match[$k + 1]);
                    }
                    //如果不是controller 也不是 action 则放入get参数中
                    $tmpGet[$v] = $match[$k + 1];
                    // $_GET[$v] = $match[$k + 1];
                };


                //强制转为
                if (isset($tmpGet)) {     //如果设置了
                    $mvc['get'] = array_merge($rule['default'], (array)$tmpGet); //以tmpGet去覆盖default
                } else {
                    $mvc['get'] = $rule['default'];
                }
                //合并默认参数------------》以后面一个为准
//                if(empty($tmpGet)){
//                    if(empty($rule['default'])){  //如果default也是空 那么就不管了
//
//                    }else{
//                        $mvc['get']=$rule['default'];
//                    }
//                }else {
//                    if(empty($rule['default'])){  //如果default也是空 那么就不管了
//                        $mvc['get']=$tmpGet;
//                    }else{
//                        $mvc['get']=array_merge($rule['default'],$tmpGet); //以tmpGet去覆盖default
//                    }
//                }
//                $tmpGet=array();
//                if(!empty($rule['default'])){
//                    $tmpGet=$rule['default'];
//                };
//                if(!empty($rule['default']))
//                $mvc['get']=array_merge($rule['default'],$mvc['get']);
//                echo 'begin test data*  after merge******************'.PHP_EOL;
//                var_dump($mvc['get']);
                //$_GET=array_merge($rule['default'],$_GET);

                return $mvc;
            }
        }
        return false;
    }


}
