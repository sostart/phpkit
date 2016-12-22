<?php

namespace PHPKit;

use Exception;

trait LazyLinkTrait
{
    public function __call($name, $arguments)
    {
        return static::__callStatic($name, $arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        $instance = static::getInstance();
        
        $name = 'API_'.$name;

        if (!method_exists($instance, $name)) {
            throw new Exception(__CLASS__. " $name ". ' 未定义');
        }
        
        return is_null( $return = call_user_func_array([$instance, $name], $arguments) ) ? $instance: $return; 
    }
}