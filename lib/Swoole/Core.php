<?php
namespace Swoole;
/**
 * Swoole系统核心类，外部使用全局变量$php引用
 * Swoole框架系统的核心类，提供一个swoole对象引用树和基础的调用功能
 */

class Core
{
    /**
     * Swoole类的实例
     * @var Swoole
     */
    static public $php;

    public $env;
    protected $hooks = array();

    const HOOK_INIT = 1; //初始化
    const HOOK_ROUTE = 2; //URL路由
    const HOOK_CLEAN = 3; //清理

    private function __construct()
    {
        if (!defined('DEBUG')) define('DEBUG', 'on');
        if (DEBUG == 'off') \error_reporting(0);

        $this->env['sapi_name'] = php_sapi_name();
    }

    static function getInstance()
    {
        if (!self::$php) {
            self::$php = new Swoole;
        }
        return self::$php;
    }

    /**
     * 初始化环境
     * @return unknown_type
     */
    function __init()
    {
        if (defined('DEBUG') and DEBUG == 'on') {
            //记录运行时间和内存占用情况
            $this->env['runtime']['start'] = microtime(true);
            $this->env['runtime']['mem'] = memory_get_usage();
        }
        $this->callHook(self::HOOK_INIT);
    }

    /**
     * 执行Hook函数列表
     * @param $type
     */
    protected function callHook($type)
    {
        if (isset($this->hooks[$type])) {
            foreach ($this->hooks[$type] as $f) {
                if (!is_callable($f)) {
                    trigger_error("SwooleFramework: hook function[$f] is not callable.");
                    continue;
                }
                $f();
            }
        }
    }

    /**
     * 获取资源消耗
     * @return unknown_type
     */
    function runtime()
    {
        // 显示运行时间
        $return['time'] = number_format((microtime(true) - $this->env['runtime']['start']), 4) . 's';

        $startMem = array_sum(explode(' ', $this->env['runtime']['mem']));
        $endMem = array_sum(explode(' ', memory_get_usage()));
        $return['memory'] = number_format(($endMem - $startMem) / 1024) . 'kb';
        return $return;
    }

    function __clean()
    {
        $this->env['runtime'] = array();
        $this->callHook(self::HOOK_CLEAN);
    }
}