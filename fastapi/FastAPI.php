<?php

namespace PHPKit;

use Exception;

class FastAPI
{
    const ERROR_NOAPI = 1;

    use LazySingletonTrait, LazyLinkTrait;

    use FastAPI\ResponseTrait, FastAPI\RESTFulTrait;

    protected static $group = '';

    protected static $middlewares = [];

    protected static $container = [];

    protected static function init()
    {
        
    }

    protected static function API_group($uri, $middlewares, $callable=null)
    {
        static::register(chr(29).$uri, $middlewares, $callable);
    }

    protected static function API_register($uri, $middlewares, $callable=null)
    {
        $uri = $uri=='/'?$uri:trim($uri, '/');

        if (is_null($callable)) {
            $callable = $middlewares;
            $middlewares = [];
        }

        $middlewares = array_merge(static::$middlewares, (array)$middlewares);
        
        ($uri && $uri[0]==chr(29)) ? (($uri = substr($uri, 1)) && ($expanded = false)) : $expanded = true;

        $fulluri = trim(static::$group.( $uri[0]==chr(0) ? ('/'.ltrim($uri, '/')) : '/'.$uri), '/')?:'/';

        static::$container[$fulluri] = [
            'expanded' => $expanded,
            'middlewares' => &$middlewares,
            'callable' => &$callable
        ];
    }
    
    // 只能切换顶级分组
    protected static function API_switchGroup($group)
    {
        if (!isset(static::$container[$group])) {
            throw new Exception('不存在的分组 '.$group);
        } else {
            static::$group = $group;
        }
    }

    protected static function API_request()
    {
        $method = false;
        $params = [];
        $callback = false;

        foreach (func_get_args() as $param) {
            if (is_string($param)) {
                if (in_array($param, ['POST', 'DELETE', 'GET', 'PUT', 'PATCH', 'OPTIONS'])) {
                    $method = $param;
                } else {
                    $uri = trim($param);
                    $uri = $uri=='/'?$uri:trim($uri, '/');
                }
            } elseif (is_array($param)) {
                $params = $param;
            } elseif (is_callable($param)) {
                $callback = $param;
            }
        }

        if (!isset($uri)) {
            throw new Exception('uri/pathinfo 必须');
        }

        $return = static::request($uri, $params, $callback);
        if ( $method && isset($return['_c']) && $return['_c']==static::ERROR_NOAPI) {
            $return = static::request($uri.chr(0).' '.strtoupper($method), $params, $callback);
        }
        return $return;
    }

    protected static function request($uri, $params, $callback=false)
    {
        static::responseReset();

        $uri = trim($uri, '/');
        
        $instance = static::getInstance();

        $fulluri = trim(static::$group.'/'.$uri, '/');
        
        $parturi = '';

        foreach (explode('/', $fulluri) as $part) {
            
            $break = true;
            
            if ($part) {
                $parturi = trim($parturi.'/'.$part, '/');
            } else {
                $fulluri = $parturi = '/';
            }

            if (isset(static::$container[$parturi]) && static::$container[$parturi]['expanded']==false ) {
                
                $break = false;

                $group = static::$group; static::$group = $parturi;
                $middlewares = static::$middlewares; static::$middlewares = static::$container[$parturi]['middlewares'];
                    
                    $func = static::$container[$parturi]['callable'];
                    unset(static::$container[$parturi]);
                    call_user_func($func, $params, $group, $uri);

                static::$group = $group;
                static::$middlewares = $middlewares;
            }

            // bingo
            if (isset(static::$container[$fulluri]) && static::$container[$fulluri]['expanded']) {
                
                static::$container[$fulluri]['middlewares'][] = function ($params, $next) use ($instance) {
                    $data = $next($params);
                    return $instance->setData($data);
                };

                $data = call_user_func(array_reduce(array_reverse(static::$container[$fulluri]['middlewares']), function ($next, $pipe) {
                    return function ($params) use ($next, $pipe) {
                        return call_user_func($pipe, $params, $next);
                    };
                }, static::$container[$fulluri]['callable']), $params);

                if (is_null($data)) {
                    throw new Exception('接口没有返回值(请检查中间件)');
                }

                break;
            }

            if ($break) {
                break;
            }
        }
        
        if (isset($data)) {
            return $callback ? $callback($data) : $data;
        } else {
            return static::error('不存在的接口 '.$uri, static::ERROR_NOAPI);
        }
    }

    public function __invoke()
    {
        $instance = static::getInstance();

        return $instance;    
    }
}
