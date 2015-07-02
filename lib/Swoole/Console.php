<?php
namespace Swoole;

class Console
{
    static function getOpt($cmd)
    {
        $cmd = trim($cmd);
        $args = explode(' ',$cmd);
        $return = array();
        foreach($args as &$arg)
        {
            $arg = trim($arg);
            if(empty($arg)) unset($arg);
            if($arg{0}==='\\' or $arg{0}==='-')  $return['opt'][] = substr($arg,1);
            else $return['args'][] = $arg;
        }
        return $return;
    }

    /**
     * 改变进程的用户ID
     * @param $user
     */
    static function changeUser($user)
    {
		if (!function_exists('posix_getpwnam'))
		{
			trigger_error(__METHOD__.": require posix extension.");
			return;
		}
        $user = posix_getpwnam($user);
        if($user)
        {
            posix_setuid($user['uid']);
            posix_setgid($user['gid']);
        }
    }

    static function setProcessName($name)
    {
        if (function_exists('cli_set_process_title'))
        {
            cli_set_process_title($name);
        }
        else if(function_exists('swoole_set_process_name'))
        {
            swoole_set_process_name($name);
        }
        else
        {
            trigger_error(__METHOD__." failed. require cli_set_process_title or swoole_set_process_name.");
        }
    }
}