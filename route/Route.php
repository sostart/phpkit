<?php

namespace PHPKit;

use Exception;
use PHPKit\Route\FastRoute;

class Route
{
    const FOUND = 200;
    const NOT_FOUND = 404;
    
    protected static $route;

    protected static $group = '';
    protected static $middlewares = [];

    protected static $routeGroups = [];

    protected static $dispatcher = false;

    use LazySingletonTrait, LazyLinkTrait;
    
    protected static function init()
    {
        static::$route = static::fastRoute();
    }

    public static function fastRoute()
    {
        return new FastRoute;
    }

    protected static function _setDispatcher($dispatcher)
    {
        static::$dispatcher = $dispatcher;
    }

    protected static function _group($uri, $middleware, $callable=null)
    {
        if (is_null($callable)) {
            $callable = $middleware;
            $middleware = [];
        }

        $uri = '/'.trim($uri, '/');
        $uri = (rtrim(static::$group.$uri, '/')?:'/');

        static::$routeGroups[$uri] = [$middleware, $callable];
    }

    protected static function expandGroup($fulluri)
    {
        foreach (static::$routeGroups as $uri=>$arr) {
            if (preg_match('~^'.$uri.'~i', $fulluri)) {

                $middleware = $arr[0];
                $callable = $arr[1];

                $group = static::$group;
                static::$group = static::$group.$uri;
                
                $middlewares = static::$middlewares;
                static::$middlewares = array_merge(static::$middlewares, (array)$middleware);
                
                call_user_func($callable);

                static::$middlewares = $middlewares;
                static::$group = $group;
            }
            
            unset(static::$routeGroups[$uri]);
        }

        // 在展开时有新增的路由组,继续
        if (static::$routeGroups) {
            static::expandGroup($fulluri);
        }
    }
    
    protected static function _any($uri, $middleware, $callable=null)
    {
        if (is_null($callable)) {
            $callable = $middleware;
            $middleware = [];
        }

        $uri = '/'.trim($uri, '/');
        $uri = (rtrim(static::$group.$uri, '/')?:'/');
        static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
    }

    protected static function _get($uri, $middleware, $callable=null)
    {
        if (is_null($callable)) {
            $callable = $middleware;
            $middleware = [];
        }

        $uri = '/'.trim($uri, '/');
        $uri = 'GET '.(rtrim(static::$group.$uri, '/')?:'/');
        static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
    }

    protected static function _post($uri, $middleware, $callable=null)
    {
        if (is_null($callable)) {
            $callable = $middleware;
            $middleware = [];
        }

        $uri = '/'.trim($uri, '/');
        $uri = 'POST '.(rtrim(static::$group.$uri, '/')?:'/');
        static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
    }

    protected static function _patch($uri, $middleware, $callable=null)
    {
        if (is_null($callable)) {
            $callable = $middleware;
            $middleware = [];
        }

        $uri = '/'.trim($uri, '/');
        $uri = 'PATCH '.(rtrim(static::$group.$uri, '/')?:'/');
        static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
    }

    protected static function _put($uri, $middleware, $callable=null)
    {
        if (is_null($callable)) {
            $callable = $middleware;
            $middleware = [];
        }

        $uri = '/'.trim($uri, '/');
        $uri = 'PUT '.(rtrim(static::$group.$uri, '/')?:'/');
        static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
    }

    protected static function _delete($uri, $middleware, $callable=null)
    {
        if (is_null($callable)) {
            $callable = $middleware;
            $middleware = [];
        }

        $uri = '/'.trim($uri, '/');
        $uri = 'DELETE '.(rtrim(static::$group.$uri, '/')?:'/');
        static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
    }

    protected static function _dispatch($uri=false, $method=false, $dispatcher=false)
    {
        $method = $method?:$_SERVER['REQUEST_METHOD'];
        $uri = $uri?:rawurldecode( 
            isset($_SERVER['PATH_INFO']) ? ($_SERVER['PATH_INFO']?:'/') :
            ( (false !== $pos = strpos($uri, '?')) ? substr($_SERVER['REQUEST_URI'], 0, $pos) : $_SERVER['REQUEST_URI'])
        );
        $uri = '/'.trim($uri, '/');

        // 首先解开路由组
        static::expandGroup($uri);
        
        $routeInfo = static::$route->match($uri);
        if ($routeInfo[0] == FastRoute::NOT_FOUND) {
            $uri = strtoupper($method).' '.$uri;
            $routeInfo = static::$route->match($uri);
        }

        if ($dispatcher===false) {
            $dispatcher = static::$dispatcher;
        }

        return is_callable($dispatcher)?call_user_func($dispatcher, $routeInfo):$routeInfo;
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        return $instance;
    }
}
