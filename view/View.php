<?php

namespace PHPKit;

class View
{
    use LazySingletonTrait, LazyLinkTrait;
    
    protected static $dir = [];

    protected static function API_setViewsDir($dir)
    {
        static::$dir = array_map(function ($v) {
            return rtrim($v, '\/');
        }, (array)$dir);
    }

    protected static function API_addViewsDir($dir)
    {
        foreach ((array)$dir as $v) {
            array_unshift(static::$dir, rtrim($v, '\/'));
        }
    }

    protected static function API_removeViewsDir($dir)
    {
        if ($index = array_search($dir, static::$dir)) {
            unset(static::$dir[$index]);
        }
    }

    protected static function API_render($view, $data=null)
    {
        foreach (static::$dir as $dir) {
            $file = $dir.DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, $view).'.php';
            if (file_exists($file)) {
                return call_user_func(function () {
                    is_array(func_get_arg(1)) && extract(func_get_arg(1));
                    ob_start();
                    ob_implicit_flush(false);
                    include func_get_arg(0);
                    return ob_get_clean();
                }, $file, $data);
            }
        }

        return false;
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        return func_num_args() ? call_user_func_array(['static', 'render'], func_get_args()) : $instance;
    }
}