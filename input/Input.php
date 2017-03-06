<?php

namespace PHPKit;

class Input
{
    use LazySingletonTrait, LazyLinkTrait;
    
    protected static $storage = [];

    protected static function init()
    {
        static::$storage = $_REQUEST;
    }

    protected static function API_has($key)
    {
        $return = static::$storage;

        foreach (explode('.', $key) as $sub) {
            if (isset($return[$sub])) {
                $return = $return[$sub];
            } else {
                return false;
            }
        }

        return true;
    }

    protected static function API_get($key, $default='')
    {
        $return = static::$storage;

        foreach (explode('.', $key) as $sub) {
            if (isset($return[$sub])) {
                $return = $return[$sub];
            } else {
                return $default;
            }
        }

        return $return;
    }

    protected static function API_all()
    {
        return static::$storage;
    }

    protected static function API_except()
    {
        return array_diff_key(static::$storage, array_flip(func_get_args()));
    }

    protected static function API_only()
    {
        return array_intersect_key(static::$storage, array_flip(func_get_args()));
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        return $instance;    
    }
}
