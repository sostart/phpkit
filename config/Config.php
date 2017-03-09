<?php

namespace PHPKit;

class Config
{
    use LazySingletonTrait, LazyLinkTrait;

    protected static $__storage = [];
    
    // 从文件中载入配置
    protected static function API_load($file)
    {
        if (is_file($file)) {
            
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $filename  = pathinfo($file, PATHINFO_FILENAME);

            if ($extension=='php') {
                static::$__storage = array_merge(static::$__storage, [$filename=>include $file]);
            }
        }
    }

    // get('database') get('database.default')
    public static function get($name)
    {
        $instance = static::getInstance();

        $return = static::$__storage;

        foreach (explode('.', $name) as $key) {
            if (isset($return[$key])) {
                $return = $return[$key];
            } else {
                return null;
            }
        }

        return $return;
    }

    public static function all()
    {
        $instance = static::getInstance();
        return static::$__storage;
    }

    // set('a', 123) set('b.c', 456) set(['a'=>123, 'b.c'=>456])
    public static function set($name, $value=null)
    {
        $instance = static::getInstance();

        if (is_array($name)) {
            foreach ($name as $k=>$v) {
                static::set($k, $v);
            }
        } else {
            $return = & static::$__storage;
            foreach (explode('.', $name) as $key) {
                $return = & $return[$key];
            }
            return $return = $value;
        }
    }

    public function __get($name)
    {
        return static::get($name);
    }
    
    public function __set($name, $value=null)
    {
        return static::set($name, $value);
    }

    public function __invoke()
    {
        $instance = static::getInstance();

        if ($num = func_num_args()) {
            if ($num == 1) {
                $arg = func_get_arg(0);
                if (is_array($arg)) {
                    return $instance->set($arg);
                } else {
                    return $instance->get($arg);
                }
            } else {
                return $instance->set(func_get_arg(0), func_get_arg(1));
            }
        }

        return $instance;    
    }
}