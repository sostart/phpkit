<?php

namespace PHPKit;

use Exception;

class API
{
    use LazySingletonTrait, LazyLinkTrait;
    
    protected static $config = [];
    protected static $response = '';

    protected static function API_setConfig($config) {
        if (isset($config['domain'])) {
            $config['domain'] = rtrim($config['domain'], '/');
        }
        static::$config = $config;
    }

    public static function get($path, $params=[], $callback=false)
    {
        return static::request($path, 'GET', $params, $callback);
    }

    public static function post($path, $params=[], $callback=false)
    {
        return static::request($path, 'POST', $params, $callback);
    }

    public static function request($url, $method, $params=[], $callback=false) {
        
        if (filter_var($url, FILTER_VALIDATE_URL)===false) {
            $url = isset(static::$config['domain'])?static::$config['domain'].'/'.ltrim($url,'/'):$url;
        }

        $params = http_build_query(array_merge(static::$config['params'], $params));
        $method = strtoupper($method);
        if ($method==='GET') {
            $url .= '?'.$params;
        }

        $rs = file_get_contents($url, false, stream_context_create([  
            'http' => [  
                'method' => $method,
                'header' => 'Content-type:application/x-www-form-urlencoded',  
                'content' => $params,
            ]
        ]));

        static::$response = $rs;

        if (isset(static::$config['callback']) && is_callable(static::$config['callback'])) {
            $rs = call_user_func(static::$config['callback'], $rs, ($callback && is_callable($callback)) ? $callback : false);
        } elseif ($callback && is_callable($callback)) {
            $rs = call_user_func($callback, $rs);
        }

        return $rs;
    }

    public static function response()
    {
        return static::$response;
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        if (func_num_args()==1) {
            $config = func_get_arg(0);
            if (is_string($config)) {
                $config = ['domain'=>rtrim($config, '/')];
            }
            static::setConfig($config);
        }
        return $instance;
    }
}
