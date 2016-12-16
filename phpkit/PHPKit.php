<?php

namespace PHPKit;

use Exception;

class PHPKit
{
    use LazySingletonTrait, LazyLinkTrait;

    use DI;

    protected static $tools = [];

    protected static $classAlias = [];
    
    protected static function init()
    {
        spl_autoload_register(function($class){
            if (isset(static::$classAlias[$class])) {
                class_alias(static::$classAlias[$class], $class);
            }
        });
    }
    
    protected static function _registerTools($tools, $helper=true)
    {
        $basedir = dirname(__DIR__);

        foreach ((array)$tools as $tool=>$closure) {
            
            if (is_numeric($tool) && is_string($closure)) {
                
                $tool = $closure;

                $closure = function () use ($tool) {
                    return call_user_func([class_exists($tool)?$tool:'PHPKit\\'.$tool, 'getInstance']);
                };
            }
            
            if (is_array($closure)) {
                $dir = $closure[0].DIRECTORY_SEPARATOR.strtolower($tool);
                $closure = $closure[1];
            } else {
                $dir = $basedir.DIRECTORY_SEPARATOR .strtolower($tool);    
            }
            
            $file = $dir.DIRECTORY_SEPARATOR.$tool.'.php';

            if (is_dir($dir) && is_file($file)) {

                static::$tools[strtolower($tool)] = $tool;

                static::set(strtolower($tool), $closure); // di
                static::alias(strtolower($tool), 'PHPKit\\'.$tool); // di 别名

                static::get('loader')->addClassMap(['PHPKit\\'.$tool=>$file]);
                static::get('loader')->addPsr4('PHPKit\\'.$tool.'\\', $dir);

                if ($helper && ($file = $dir.DIRECTORY_SEPARATOR.'~helper.php') && file_exists($file)) {
                    static::loadFiles($file);
                }

            } else {
                throw new Exception($tool.' 工具不存在');
            }
        }
    }

    protected static function _loadTools($tools)
    {
        foreach ((array)$tools as $tool) {
            static::get(strtolower($tool));
        }
    }

    protected static function _loadFiles($files)
    {
        foreach ((array)$files as $file) {
            \Composer\Autoload\includeFile($file);
        }
    }

    protected static function _registerDirs($list)
    {
        foreach ($list as $namespace=>$dir) {
            static::get('loader')->addPsr4($namespace, $dir, true);
        }
    }

    protected static function _classAlias($alias, $target=false)
    {
        if (is_array($alias)) {
            static::$classAlias = array_merge(static::$classAlias, $alias);
        } else {
            static::$classAlias = array_merge(static::$classAlias, [$target=>$alias]);
        }
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        
        $num = func_num_args();

        if ($num == 1) {
            $arg = func_get_arg(0);
            if (is_array($arg)) {
                foreach ($arg as $k=>$v) {
                    $instance->config->set($k, $v);
                }
            } elseif (is_string($arg)) {
                return $instance->get($arg);
            }
        } elseif ($num == 2) {
            static::set(func_get_arg(0), func_get_arg(1));
        }

        return $instance;
    }
}

trait DI
{

    protected static $container = [];

    protected static $singleton = [];
    
    protected static $alias = [];

    public function __set($name, $value)
    {
        throw new Exception('不允许的操作');
    }

    public function __isset($name)
    {
        if (isset(static::$alias[$name])) {
            return $this->__isset(static::$alias[$name]);
        }
        return isset(static::$singleton[$name]) || isset(static::$container[$name]);
    }

    public function __get($name)
    {
        return static::get($name);
    }

    public static function get($name)
    {
        $instance = static::getInstance();

        if (isset(static::$alias[$name])) {
            return static::get(static::$alias[$name]);
        } elseif (isset(static::$singleton[$name])) {
            return static::$singleton[$name];
        } elseif (isset(static::$container[$name])) {
            static::$singleton[$name] = false; // 防止在call_user_func中重复调用
            return static::$singleton[$name] = call_user_func(static::$container[$name]);
        }
    }

    protected static function _set($list, $value=null)
    {
        if (is_string($list) && !is_null($value)) {
            $list = [$list=>$value];
        }

        if (is_array($list)) {
            foreach ($list as $name=>$closure) {
                if (is_callable($closure)) {
                    static::$container[$name] = $closure;
                } else {
                    static::$singleton[$name] = $closure;
                }
            }
        }
    }

    protected static function _alias($list, $value=null)
    {
        if (is_string($list) && !is_null($value)) {
            $list = [$list=>$value];
        }

        if (is_array($list)) {
            foreach ($list as $name=>$aliases) {
                foreach ((array)$aliases as $alias) {
                    static::$alias[$alias] = $name;
                }
            }
        }
    }
}
