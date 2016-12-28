<?php

namespace PHPKit;

use Exception;

class Event
{
    use LazySingletonTrait, LazyLinkTrait;

    protected static $__storage = [];

    protected static function API_listen($name, $callable)
    {
        static::$__storage[$name][] = $callable;
    }

    protected static function API_fire($name, $params=[])
    {
        foreach (static::$__storage[$name] as $callable) {
            call_user_func_array($callable, $params);
        }
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        return $instance;
    }
}
