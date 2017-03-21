<?php

namespace PHPKit\FastAPI;

trait RESTFulTrait
{
    protected static function API_post($uri, $middlewares, $callable=null)
    {
        static::register($uri.chr(0).' POST', $middlewares, $callable);    
    }

    protected static function API_delete($uri, $middlewares, $callable=null)
    {
        static::register($uri.chr(0).' DELETE', $middlewares, $callable);
    }

    protected static function API_get($uri, $middlewares, $callable=null)
    {
        static::register($uri.chr(0).' GET', $middlewares, $callable);
    }

    protected static function API_put($uri, $middlewares, $callable=null)
    {
        static::register($uri.chr(0).' PUT', $middlewares, $callable);    
    }

    protected static function API_patch($uri, $middlewares, $callable=null)
    {
        static::register($uri.chr(0).' PATCH', $middlewares, $callable);    
    }

    protected static function API_options($uri, $middlewares, $callable=null)
    {
        static::register($uri.chr(0).' OPTIONS', $middlewares, $callable);    
    }

    protected static function API_match($methods, $uri, $middlewares, $callable=null)
    {
        foreach ($methods as $method) {
            static::register($uri.chr(0).' '.strtoupper($method), $middlewares, $callable);
        }
    }

    protected static function API_any($uri, $middlewares, $callable=null)
    {
        static::register($uri, $middlewares, $callable);
    }
}
