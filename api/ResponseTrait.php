<?php

namespace PHPKit\API;

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
    
    protected static function _error($msg, $code=1)
    {
        return static::setError($msg, $code);
    }

    protected static function _setError($msg, $code)
    {
        return static::$data = ['_c'=>$code, '_m'=>$msg];
    }

    protected static function _getError()
    {
        return static::$data['_c'] ? static::$data : false;
    }

	protected static function _setData($data)
	{
		static::$data['_d'] = $data;
        return static::getData();
	}

	protected static function _setMsg($msg)
	{
		static::$data['_m'] = $msg;
	}

	protected static function _getData()
	{
		return static::$data;
	}
}
