<?php

namespace PHPKit;

trait LazySingletonTrait
{
    static $instance;

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