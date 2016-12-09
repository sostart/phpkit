<?php

namespace PHPKit;

use Exception;

trait LazyTrait
{
    use LazySingletonTrait, LazyLinkTrait;
}

trait LazySingletonTrait
{
    static $instance;
    
    // 所有工具类不允许外部实例化(有且只有一个实例, getInstance 从内部创建)
    protected function __construct()
    {
    
    }

    protected static function init()
    {

    }

    public static function getInstance()
    {
        if (!static::$instance) {
            
            static::$instance = new static;
            PHPKit::get(__CLASS__);
            static::init();
        
        }

        return static::$instance;
    }
}

trait LazyLinkTrait
{
    public function __call($name, $arguments)
    {
        return static::__callStatic($name, $arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        $instance = static::getInstance();
        
        $name = '_'.$name;

        if (!method_exists($instance, $name)) {
            throw new Exception($name . ' 未定义');
        }
        
        return is_null( $return = call_user_func_array([$instance, $name], $arguments) ) ? $instance: $return; 
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        return $instance;
    }
}