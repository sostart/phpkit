<?php

namespace PHPKit;

use Exception;

class Heresy
{
    use LazySingletonTrait, LazyLinkTrait;

    protected static $bewitch = [];

    // 被bewitch的名字空间 自动载入类的时候会在这些名字空间下搜索
    // 存在多个同名类时 加载最先找到的 (这里顺序靠前的会优先加载)
    protected static $searchNamespace  = [];

    public static function init()
    {
        spl_autoload_register(['PHPKit\Heresy', 'autoload']);
    }

    protected static function autoload($class)
    {
        // 如果类的名字空间 在searchNamespace内 则跳出
        foreach (static::$searchNamespace as $namespace) {
            if (strpos($class, $namespace)===0) {
                return false;
            }
        }
        
        // 被蛊惑的名字空间下  加载失败的类 从searchNamespace下尝试加载
        $class = trim($class, '\\');
        $root = ($pos = strpos($class, '\\')) ? substr($class, 0, $pos) : '\\';
        if ( (isset(static::$bewitch['\\']) && static::$bewitch['\\']) || (isset(static::$bewitch[$root]) && static::$bewitch[$root]) ) {
            foreach (static::$searchNamespace as $namespace) {
                $name = ($pos = strrpos($class, '\\')) ? substr($class, $pos+1) : $class;
                $search_class = $namespace . $name;
                if ((class_exists($search_class) || trait_exists($search_class)) && class_alias($search_class, $class)) {
                    break;
                }
            }
        }

        return true;
    }

    protected static function API_searchNamespace($list)
    {
        static::$searchNamespace = array_merge((array)$list, static::$searchNamespace);
    }

    protected static function API_bewitch($namespace)
    {
        foreach ((array)$namespace as $one) {
            $one = trim($one, '\\')?:'\\';
            $root = ($pos = strpos($one, '\\')) ? substr($one, 0, $pos) : $one; // 从根域开始
            static::$bewitch[$root] = true;
        }
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        return $instance;
    }
}