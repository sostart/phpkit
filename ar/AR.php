<?php

namespace PHPKit;

class AR
{
    use LazySingletonTrait, LazyLinkTrait;
    
    protected static $handle;

    protected static $storage=[];

    protected static function _handle($handle)
    {
        static::$handle = $handle;
    }

	public static function get($name)
	{
		static::getInstance();
        return isset(static::$storage[$name])? static::$storage[$name] : (
            static::$storage[$name] = new \PHPKit\AR\ActiveRecord([
                'table'=>$name,
                'handle'=>static::$handle
            ])
        );
	}

    public function __invoke()
    {
        $instance = static::getInstance();

        return $instance;    
    }
}
