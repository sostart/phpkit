<?php

namespace PHPKit\API;

trait RESTFulTrait
{
    protected static function _post($uri, $middlewares, $callable=null)
    {
        static::register($uri.chr(0).' POST', $middlewares, $callable);    
    }

    protected static function _delete($uri, $middlewares, $callable=null)
    {
        static::register($uri.chr(0).' DELETE', $middlewares, $callable);
    }

    protected static function _get($uri, $middlewares, $callable=null)
    {
        static::register($uri.chr(0).' GET', $middlewares, $callable);
    }

    protected static function _put($uri, $middlewares, $callable=null)
    {
        static::register($uri.chr(0).' PUT', $middlewares, $callable);    
    }

    protected static function _patch($uri, $middlewares, $callable=null)
    {
        static::register($uri.chr(0).' PATCH', $middlewares, $callable);    
    }

    protected static function _options($uri, $middlewares, $callable=null)
    {
        static::register($uri.chr(0).' OPTIONS', $middlewares, $callable);    
    }

    protected static function _match($methods, $uri, $middlewares, $callable=null)
    {
        foreach ($methods as $method) {
            static::register($uri.chr(0).' '.strtoupper($method), $middlewares, $callable);
        }
    }

    protected static function _any($uri, $middlewares, $callable=null)
    {
        static::register($uri, $middlewares, $callable);
    }
}
