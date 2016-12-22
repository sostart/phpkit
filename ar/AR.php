<?php

namespace PHPKit;

class AR
{
    use LazySingletonTrait, LazyLinkTrait;
    
    protected static $handle;

    protected static $storage=[];

    protected static function API_handle($handle)
    {
        static::$handle = $handle;
    }

	protected static function API_get($name)
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
