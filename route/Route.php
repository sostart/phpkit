<?php

namespace PHPKit;

use Exception;
use PHPKit\Route\FastRoute;

class Route
{
    const FOUND = FastRoute::FOUND;
    const NOT_FOUND = FastRoute::NOT_FOUND;
    
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

    protected static function API_fastRoute()
    {
        return new FastRoute;
    }

    protected static function API_setDispatcher($dispatcher)
    {
        static::$dispatcher = $dispatcher;
    }

    protected static function API_group($uri, $middleware, $callable=null)
    {
        if (is_null($callable)) {
            $callable = $middleware;
            $middleware = [];
        }

        $uri = '/'.trim($uri, '/');
        $uri = '/'.trim(static::$group.$uri, '/');

        static::$routeGroups[$uri] = [$middleware, $callable];
    }

    protected static function expandGroup($fulluri)
    {
        $routeGroups = static::$routeGroups;
        static::$routeGroups = [];
        foreach ($routeGroups as $uri=>$arr) {
            if (preg_match('~^'.$uri.'~i', $fulluri)) {

                $middleware = $arr[0];
                $callable = $arr[1];

                $group = static::$group;
                static::$group = '/'.trim($uri, '/');
                
                $middlewares = static::$middlewares;
                static::$middlewares = array_merge(static::$middlewares, (array)$middleware);
                
                call_user_func($callable);
                
                // 在展开时有新增的路由组,则继续展开
                if (static::$routeGroups) {
                    static::expandGroup($fulluri);
                }

                static::$middlewares = $middlewares;
                static::$group = $group;
            }
        }
    }
    
    protected static function API_any($uri, $middleware, $callable=null)
    {
        if (is_null($callable)) {
            $callable = $middleware;
            $middleware = [];
        }

        $uri = (rtrim('/'.trim(static::$group.'/'.trim($uri, '/'), '/'), '/')?:'/');

        static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
    }

    protected static function API_get($uri, $middleware, $callable=null)
    {
        if (is_null($callable)) {
            $callable = $middleware;
            $middleware = [];
        }

        $uri = 'GET '.(rtrim('/'.trim(static::$group.'/'.trim($uri, '/'), '/'), '/')?:'/');

        static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
    }

    protected static function API_post($uri, $middleware, $callable=null)
    {
        if (is_null($callable)) {
            $callable = $middleware;
            $middleware = [];
        }

        $uri = 'POST '.(rtrim('/'.trim(static::$group.'/'.trim($uri, '/'), '/'), '/')?:'/');

        static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
    }

    protected static function API_patch($uri, $middleware, $callable=null)
    {
        if (is_null($callable)) {
            $callable = $middleware;
            $middleware = [];
        }

        $uri = 'PATCH '.(rtrim('/'.trim(static::$group.'/'.trim($uri, '/'), '/'), '/')?:'/');

        static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
    }

    protected static function API_put($uri, $middleware, $callable=null)
    {
        if (is_null($callable)) {
            $callable = $middleware;
            $middleware = [];
        }

        $uri = 'PUT '.(rtrim('/'.trim(static::$group.'/'.trim($uri, '/'), '/'), '/')?:'/');

        static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
    }

    protected static function API_delete($uri, $middleware, $callable=null)
    {
        if (is_null($callable)) {
            $callable = $middleware;
            $middleware = [];
        }

        $uri = 'DELETE '.(rtrim('/'.trim(static::$group.'/'.trim($uri, '/'), '/'), '/')?:'/');

        static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
    }

    protected static function API_dispatch($uri=false, $method=false, $dispatcher=false)
    {
        $method = $method?:(isset($_REQUEST['_method'])?strtoupper($_REQUEST['_method']):(isset($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:'GET'));
        $uri = $uri?:rawurldecode( 
            isset($_SERVER['PATH_INFO']) ? ($_SERVER['PATH_INFO']?:'/') :
            ( isset($_SERVER['REQUEST_URI']) ? ((false !== $pos = strpos($_SERVER['REQUEST_URI'], '?')) ? substr($_SERVER['REQUEST_URI'], 0, $pos) : $_SERVER['REQUEST_URI']) : '/')
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
