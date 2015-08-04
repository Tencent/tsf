<?php

/**
 * @author winterswang
 * test for auto_load
 */
class AutoLoad
{

    private static $root_path = array();

    /**
     * [set_include_path 完成根目录下目录的扫描，支持多层目录嵌套]
     * @param [type] $dir [路径]
     */

    private static function set_include_path($dir)
    {
        $arr = scandir($dir);
        $include_paths = array();
        //读取所有的文件夹目录   前两个是.和.. 可以去掉
        for ($i = 2; $i < count($arr); $i++) {
            if (is_dir($dir . DIRECTORY_SEPARATOR . $arr[$i])) {
                $include_paths = array_merge($include_paths, self::set_include_path($dir . DIRECTORY_SEPARATOR . $arr[$i]));
            }
            $include_paths[] = $dir;
        }
        return array_unique($include_paths);
    }

    /**
     * [auto_load]
     * @param  [type] $className [文件名]
     * @return [type]            [description]
     */
    public static function auto_load($className)
    {
        $time1 = microtime(true);
        $pathArr = array();
        //root_path 为空，默认添加src目录
        if (empty(self::$root_path)) {
            self::$root_path[] = dirname(dirname(__FILE__));
        }
        foreach (self::$root_path as $key => $root) {
            $pathArr = array_merge($pathArr, self::set_include_path($root));
        }

        //支持命名空间
        $namePath = explode('\\', trim($className, '\\'));
        if (count($namePath) > 1 && (PHP_OS != 'WINNT')) {
            $className = implode($namePath, '/');
        }
        foreach ($pathArr as $key => $path) {
            $class_file = $path . DIRECTORY_SEPARATOR . $className . ".php";
            if (is_file($class_file)) {
                include_once($class_file);

                break;
            }
        }
        //unset($pathArr);
    }

    /**
     * [setRoot 设置root根目录，可以同时添加多个]
     * @param array $root [array]
     */
    public static function setRoot($rootArr = array())
    {
        if (is_array($rootArr)) {
            self::$root_path = array_merge(self::$root_path, $rootArr);
        }
    }

    /**
     * [addRoot 添加root节点，可以多节点实现auto_load]
     * @param [type] $root [description]
     */
    public static function addRoot($root)
    {
        if (isset($root)) {
            self::$root_path[] = $root;
        }
    }

    /**
     * [getFatherPath 获取父级目录路径]
     * @param  [type]  $path [description]
     * @param  integer $num [父级的级数，默认是当前目录的上一级目录]
     * @return [type]        [路径字符串]
     */
    public static function getFatherPath($path, $num = 1)
    {
        if (!isset($path)) {
            //未设置路径，返回当前路径
            return dirname(__FILE__);
        }
        $needle = (PHP_OS == 'WINNT') ? '\\' : '/';
        for ($i = 0; $i < $num; $i++) {
            $path = substr($path, 0, strrpos($path, $needle));
        }
        return $path;
    }
}

spl_autoload_register(array('AutoLoad', 'auto_load'));
?>