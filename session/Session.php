<?php

namespace PHPKit;

class Session
{
    use LazySingletonTrait, LazyLinkTrait;
    
    protected static $config=[];

    protected static function setConfig($config)
    {
        static::$config = $config;

        if ($config['driver']!='file') {
            session_set_save_handler(static::getHandle($config['driver']));
        }

        if (isset($_REQUEST[$config['tokenid']])) {
            session_name($config['tokenid']);
            session_id($_REQUEST[$config['tokenid']]);

            if (isset($_COOKIE[$config['cookieid']])) {
                setCookie($config['cookieid'], '', time()-3600, '/');
            }
        } elseif (isset($_COOKIE[$config['cookieid']])) {
            session_name($config['cookieid']);
            session_id($_COOKIE[$config['cookieid']]);
        } else {
            session_name($config['cookieid']);
            session_id();
        }
    }

    protected static function _start($config)
    {
        static::setConfig($config);
        session_start();
    }

    protected static function getHandle($driver)
    {
        $class = 'PHPKit\Session\\Adapter\\'.ucfirst($driver).'Session';
        return new $class(static::$config);
    }
    
    public static function set($key, $val)
    {
        $instance = static::getInstance();
        return $_SESSION[$key] = $val;
    }

    public static function get($key)
    {
        $instance = static::getInstance();
        return isset($_SESSION[$key])?$_SESSION[$key]:null;
    }

    public static function delete($key)
    {
        $instance = static::getInstance();
        if (isset($_SESSION[$key]))  unset($_SESSION[$key]);
        return true;
    }

    public static function destroy()
    {
        $instance = static::getInstance();
        return session_destroy();
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        
        $num = func_num_args();
        if ($num == 1) {
            return static::get(func_get_arg(0));
        } elseif ($num == 2) {
            if (is_null(func_get_arg(1))) {
                return static::delete(func_get_arg(0));
            } else {
                return static::set(func_get_arg(0), func_get_arg(1));
            }
        }

        return $instance;    
    }
}
