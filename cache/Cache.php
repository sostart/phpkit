<?php

namespace PHPKit;

use Exception;

class Cache
{
    use LazySingletonTrait, LazyLinkTrait;
    
    protected static $config = [];

    protected static $conname = '';
    protected static $prefix = '';

    protected static $handles = [];

    
    protected static function API_setConfig($config)
    {
        static::$config = $config;
        static::$conname = $config['default'];
        static::$prefix = isset($config['prefix'])?$config['prefix']:'';
    }

    protected static function API_getHandle()
    {
        if (isset(static::$handles[static::$conname])) {
            return static::$handles[static::$conname];
        }

        $config = static::$config['stores'][static::$conname];
        $class = 'PHPKit\Cache\Adapter\\'.ucfirst($config['driver']).'Cache';
        
        return static::$handles[static::$conname] = new $class($config);
    }

    public static function set($key, $value, $expire=0)
    {
        return static::getHandle()->set(static::getPrefix().$key, $value, $expire);
    }
    
    public static function add($key, $value, $expire=0)
    {
        return static::getHandle()->add(static::getPrefix().$key, $value, $expire);
    }
    
    public static function get($key, $default=null)
    {
        return static::getHandle()->get(static::getPrefix().$key, $default);
    }
    
    public static function delete($key)
    {
        return static::getHandle()->delete(static::getPrefix().$key);
    }

    public static function flush()
    {
        return static::getHandle()->flush();
    }

    public static function increment($key, $offset=1)
    {
        return static::getHandle()->increment(static::getPrefix().$key, $offset);
    }

    public static function decrement($key, $offset=1)
    {
        return static::getHandle()->decrement(static::getPrefix().$key, $offset=1);
    }

    public static function getPrefix()
    {
        return static::$prefix;
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

interface CacheInterface
{
    public function __construct($config);
    public function set($key, $value, $expire=0);
    public function add($key, $value, $expire=0);
    public function get($key, $default=null);
    public function delete($key);
    public function flush();
    public function increment($key, $offset=1);
    public function decrement($key, $offset=1);
}
