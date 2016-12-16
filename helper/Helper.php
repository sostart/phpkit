<?php

namespace PHPKit;

use Exception;

class Helper
{
    use LazySingletonTrait, LazyLinkTrait;

    protected static $storage = [];

    public static function register($name, $callable)
    {
        $instance = static::getInstance();
        $instance->$name = $callable;
        return $instance;
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
            return call_user_func_array(static::$storage[$name], $arguments);
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
