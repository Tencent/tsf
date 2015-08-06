<?php

/**
 * @Author: winterswang
 * @Date:   2015-04-24 11:49:21
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-05-20 17:39:37
 */
class SysLog
{

    //默认全开
    public static $logLevel = array(
        'debug' => 'debug',
        'info' => 'info',
        'notice' => 'notice',
        'warning' => 'warning',
        'error' => 'error',
    );  // debug info notice warning error

    public static $defaultPermission = 0777;

    //默认log目录
    public static $logDir = '/tmp/log';

    public static $enable_task_log = false;


    /**
     * [asyncWriteLog 调用swoole的异步写文件的方法，写log]
     * @param  [type] $logInfo  [description]
     * @param  [type] $filePath [description]
     * @return [type]           [description]
     */
    /*	public static function asyncWriteLog($logInfo, $filePath){
            //打开文件
            //写文件
            //反回状态
            swoole_async_write($filePath, $logInfo, -1, function($file, $writen){
                //echo "write [$writen] \n";
                //echo "file [$file] \n";
                return true;
            });
        }
    */
    /**
     * [formatMessage description]
     * @param  [type] $level   [description]
     * @param  [type] $message [description]
     * @return [type]          [description]
     */
    private static function formatMessage($level, $message)
    {
        $level = strtoupper($level);
        $time = date('Y-m-d H:i:s');
        return "[{$time}] [{$level}] {$message}" . PHP_EOL;
    }

    /**
     * [log description]
     * @param  [type] $log      [log内容]
     * @param  [type] $logName [log文件名]
     * @param  [type] $logLevel [日志等级]
     * @return [type]           [false,true]
     */
    private static function log($log, $logName, $logLevel)
    {

        if (empty(self::$logDir)) {
            //初始化，如果失败，返回
            if (!self::init()) {
                return false;
            }
        }

        if (empty(self::$logLevel) || !in_array($logLevel, self::$logLevel)) {
            //不在log等级内，返回
            return false;
        }

        if (!isset($logName)) {
            //没有设置log名，返回
            return false;
        }

        $dir = self::$logDir . '/bak_' . date('Ymd');
        $dirArr = explode('/', trim($dir));
        $tmpDir = '';
        $faDir = '';

        for ($i = 1; $i < count($dirArr); $i++) {
            $faDir .= '/' . $dirArr[$i - 1];
            $tmpDir .= '/' . $dirArr[$i];
            //判断目录是否存在
            if (!is_dir($tmpDir)) {
                //判断目录是否可写
                if (!is_writeable($faDir)) {
                    //不可写
                    return false;
                }
                //创建目录
                $res = mkdir($tmpDir, self::$defaultPermission, true);
                if (!$res) {
                    //创建失败
                    return false;
                }
            }
        }
        //目录判断和创建完成，开始写入log
        //TODO 支持IP显示
        //异步写log
        //if(!swoole_async_write($dir.'/'.$logName. '.log',self::formatMessage($logLevel,$log), -1)){
        //异步失败，同步写log
        error_log(self::formatMessage($logLevel, $log), 3, $dir . '/' . $logName . '.log');
        //	}
        return true;
    }


    /**
     * [info description]
     * @param  [type] $log     [description]
     * @param  [type] $logName [description]
     * @return [type]          [description]
     */
    public static function info($log, $logName)
    {

        return self::log($log, $logName, 'info');
    }

    /**
     * [error description]
     * @param  [type] $log     [description]
     * @param  [type] $logName [description]
     * @return [type]          [description]
     */
    public static function error($log, $logName)
    {

        return self::log($log, $logName, 'error');
    }

    /**
     * [warn description]
     * @param  [type] $log     [description]
     * @param  [type] $logName [description]
     * @return [type]          [description]
     */
    public static function warn($log, $logName)
    {

        return self::log($log, $logName, 'warning');
    }

    /**
     * [debug description]
     * @param  [type] $log     [description]
     * @param  [type] $logName [description]
     * @return [type]          [description]
     */
    public static function debug($log, $logName)
    {

        return self::log($log, $logName, 'debug');
    }

    /**
     * [notice description]
     * @param  [type] $log     [description]
     * @param  [type] $logName [description]
     * @return [type]          [description]
     */
    public static function notice($log, $logName)
    {

        return self::log($log, $logName, 'notice');
    }

    //网络日志
    public static function netLog($log, $host, $port, $log_name)
    {

    }

    /**
     * [init 初始化log配置] 有默认配置
     * @return [type] [description]
     */
    public static function init($logInfo = null)
    {

        date_default_timezone_set('PRC');


        // //判断是否开启task_log
        // if (!empty($iniArr['log_method'])) {
        // 	foreach ($iniArr['log_method'] as $key => $value) {
        // 		self::$enable_task_log = ($key == 'enable_task_log' && $value == 1) ? true : false;
        // 	}
        // }

        if (empty($logInfo)) {
            return true;
        };
        self::$logDir = isset($logInfo['log_path']) ? $logInfo['log_path'] : '/tmp/log';
        //log level set array 以用户的为准
        self::$logLevel = array();
        foreach ($logInfo['log_level'] as $key => $value) {
            if ($value) {
                self::$logLevel[$key] = $key;
            }
        }
        return true;
    }
}

//SysLog::asyncWriteLog('test','/tmp/SysLog.log');
// if (SysLog::init()) {
// 	echo "init success \n";
// }
// else{
// 	echo "init failed \n";
// }
