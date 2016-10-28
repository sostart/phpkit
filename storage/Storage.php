<?php

namespace PHPKit;

class Storage
{
    use LazySingletonTrait, LazyLinkTrait;
    
    protected static $current;
    protected static $config = [];
    protected static $handles = [];

    public static function setConfig($config)
    {
        $instance = static::getInstance();

        static::$current = $config['default'];
        static::$config = $config;

        return $instance;
    }

    public static function turn($disk)
    {
        $instance = static::getInstance();
        static::$current = $disk;
        return $instance;
    }

    public static function __callStatic($name, $arguments)
    {
        $instance = static::getInstance();
        if (!isset(static::$handles[static::$current])) {
            $config = static::$config['disks'][static::$current];
            $class = '\\PHPKit\\Storage\\Adapter\\'.$config['driver'].'Storage';
            if (class_exists($class)) {
                static::$handles[static::$current] = new $class($config);
            } else {
                throw new Exception('不支持的存储驱动 '.$config['driver']);
            }
        }
        return call_user_func_array([static::$handles[static::$current], $name], $arguments);
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        $num = func_num_args();
        if ($num == 1) {
            return static::get(func_get_arg(0));
        } elseif ($num == 2) {
            return static::put(func_get_arg(0), func_get_arg(1));
        }
        return $instance;    
    }
}