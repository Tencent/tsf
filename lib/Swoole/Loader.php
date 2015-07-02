<?php
namespace Swoole;


class Loader
{
    /**
     * 命名空间的路径
     */
    static $nsPath;

    function __construct($swoole)
    {
    }

    /**
     * 自动载入类
     * @param $class
     */
    static function autoload($class)
    {
        $root = explode('\\', trim($class, '\\'), 2);
        if (count($root) > 1 and isset(self::$nsPath[$root[0]])) {
            include_once self::$nsPath[$root[0]] . '/' . str_replace('\\', '/', $root[1]) . '.php';
        }
//        elseif (count($root) < 2) {
//            if (is_file(__DIR__ . '/' . $class . '.php')) {
//                include_once __DIR__ . '/' . $class . '.php';
//            } elseif (is_file(APP_BASE_PATH . '/class/' . $class . '.class.php')) {
//                include_once APP_BASE_PATH . '/class/' . $class . '.class.php';
//            }
//        }
    }

    /**
     * 设置根命名空间
     * @param $root
     * @param $path
     */
    static function setRootNS($root, $path)
    {
        self::$nsPath[$root] = $path;
    }
}