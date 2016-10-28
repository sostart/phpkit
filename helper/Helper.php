<?php

namespace PHPKit;

use Exception;

class Helper
{
    use LazySingletonTrait, LazyLinkTrait;

    protected static $storage = [];

    protected static function _load($list)
    {
        $instance = static::getInstance();

        foreach ($list as $class) {
            $class = $class.'Helper';
            if (class_exists($class)) {
                foreach (get_class_methods($class) as $method) {
                    $instance->$method = [$class, $method];                
                }
            } else {
                throw new Exception($class . ' 未找到');
            }
        }
    }

    public function __set($key, $val=null)
    {
        return static::$storage[$key] = $val;
    }

    public static function __callStatic($name, $arguments)
    {
        $instance = static::getInstance();
        
        if (method_exists($instance, $name)) {
            return is_null( $return = call_user_func_array([$instance, $name], $arguments) ) ? $instance: $return;
        } elseif (isset(static::$storage[$name])) {
            return call_user_func_array([static::$storage[$name][0], static::$storage[$name][1]], $arguments);
        } else {
            throw new Exception($name . ' 未定义');
        }
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        return $instance;
    }
}