<?php

namespace PHPKit\FastAPI;

trait ResponseTrait
{

	protected static $data = [ 
		'_c'=>0,  // 状态码
		'_d'=>'', // 返回值
	];

    protected static function responseReset()
    {
        static::$data = ['_c'=>0, '_d'=>''];
    }
    
    protected static function API_error($msg, $code=1)
    {
        return static::setError($msg, $code);
    }

    protected static function API_setError($msg, $code)
    {
        return static::$data = ['_c'=>$code, '_m'=>$msg];
    }

    protected static function API_getError()
    {
        return static::$data['_c'] ? static::$data : false;
    }

	protected static function API_setData($data)
	{
		static::$data['_d'] = $data;
        return static::getData();
	}

	protected static function API_setMsg($msg)
	{
		static::$data['_m'] = $msg;
	}

	protected static function API_getData()
	{
		return static::$data;
	}
}
