<?php

namespace PHPKit;

use Exception;

class Response
{
    use LazySingletonTrait, LazyLinkTrait;
    
    protected static $responseType = 'html'; // html => text/html json => application/json
    protected static $response = ['status'=>200, 'message'=>'OK', 'content'=>''];

    protected static function API_status($status=null)
    {
        if (!is_null($status)) {
            static::$response['status'] = $status;
        } else {
            return static::$response['status'];
        }
    }

    protected static function API_message($message=null)
    {
        if (!is_null($message)) {
            static::$response['message'] = $message;
        } else {
            return static::$response['message'];
        }
    }

    protected static function API_content($content=null)
    {
        if (!is_null($content)) {
            static::$response['content'] = $content;
        } else {
            return static::$response['content'];
        }
    }

    protected static function API_header($key, $val='')
    {
        if (is_array($key)) {
            foreach ($key as $k=>$v) {
                static::header($k, $v);
            }
        } else {
            static::$response['headers'][$key] = $val;
        }
    }

    protected static function API_json($callback=null)
    {
        
        static::type('json');

        if (!is_null($callback)) {
            if ($callback===false) {
                static::type('html');
            } else {
                static::callback($callback);
            }
        }
    }

    protected static function API_type($type)
    {
        static::$responseType = $type;
    }

    protected static function API_callback($callback)
    {
        static::$response['jsonp_callback'] = $callback;
    }

    public function __toString()
    {
        if (static::$responseType=='json') {
            header('Content-type: application/json');
            $response = json_encode([
                'status'=>static::$response['status'], 'message'=>static::$response['message'], 'data'=>static::$response['content']
            ], JSON_UNESCAPED_UNICODE);

            if (isset(static::$response['jsonp_callback'])) {
                return static::$response['jsonp_callback'].'('.$response.')';
            } else {
                return $response;
            }
        } else {
            header('HTTP/1.1 '.static::status().' '.static::message());
            return (string)static::content();
        }
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        if ($num = func_num_args()) {
            if ($num == 1) {
                $content = func_get_arg(0);
                static::content($content);
            } elseif ($num == 2) {
                $content = func_get_arg(0);
                $status = func_get_arg(1);
                static::content($content)->status($status);
            }
        }
        return $instance;    
    }
}
