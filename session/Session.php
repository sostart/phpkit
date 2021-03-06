<?php

namespace PHPKit;

class Session
{
    use LazySingletonTrait, LazyLinkTrait;
    
    protected static $config=[];

    protected static function API_setConfig($config)
    {
        static::$config = $config;

        if ($config['driver']!='file') {
            session_set_save_handler(static::getHandle($config['driver']));
        } else {
            if (isset($config['save_path'])) {
                session_save_path($config['save_path']);
            }
        }

        if (isset($_COOKIE[$config['cookie_name']]) && !empty($_COOKIE[$config['cookie_name']])) {
            session_name($config['cookie_name']);
            session_id($_COOKIE[$config['cookie_name']]);
        } else {
            session_name($config['cookie_name']);
            session_id();
        }
    }

    protected static function API_start($config=[])
    {
        if ($config) {
            static::setConfig($config);
        }

        session_start();
    }

    protected static function API_getID()
    {
        return session_id();
    }

    protected static function getHandle($driver)
    {
        $class = 'PHPKit\Session\Adapter\\'.ucfirst($driver).'Session';
        return new $class(static::$config);
    }
    
    public static function set($name, $value=null)
    {
        $instance = static::getInstance();

        if (is_array($name)) {
            foreach ($name as $k=>$v) {
                static::set($k, $v);
            }
        } else {
            $return = & $_SESSION;
            foreach (explode('.', $name) as $key) {
                $return = & $return[$key];
            }
            return $return = $value;
        }
    }

    public static function get($name)
    {
        $instance = static::getInstance();

        $return = $_SESSION;

        foreach (explode('.', $name) as $key) {
            if (isset($return[$key])) {
                $return = $return[$key];
            } else {
                return null;
            }
        }

        return $return;
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
        return @session_destroy();
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
