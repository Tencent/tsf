<?php
/**
 * @Author: winterswang
 * @Date:   2015-01-22 15:13:41
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-07-03 16:58:18
 */





//用来配置用户自定义的路由规则 以及一些log级别等
 class  UserConfig {
     public  static  $UserConf=array(
         'log'=>array('log_path'=>'/data/log/',
             'log_level'=>array(
                 'info'=>1,
                 'warning'=>1,
                 'notice'=>1,
                 'error'=>1,
                 'debug'=>1,
             )
         ), //支持log不同级别
         'router'=>array('info'=>1,'error'=>1), //支持自定义route 传递一个函数？
         'preFilter'=>array('info'=>1,'error'=>1), //fliter之前做的事情 传递一个函数？
         'postFilter'=>array('info'=>1,'error'=>1), //fliter之后做的事情 传递一个函数？
     );

     public static function getConfig($val) {
         return self::$UserConf[$val];
     }



}
?>