<?php

namespace PHPKit;

class Email
{
    use LazySingletonTrait, LazyLinkTrait;

    protected static $config = [];
    protected static $mailers = [];
    protected static $active = '';
    
    protected static function API_setConfig($config)
    {
        static::$config = $config;
        static::switchTo($config['default']);
    }

    protected static function API_switchTo($active)
    {
        static::$active = $active;
    }

    public static function getHandle()
    {
        $instance = static::getInstance();

        if (!isset(static::$mailers[static::$active])) {
            $config = static::$config['mailers'][static::$active];
            $class = 'PHPKit\Email\Adapter\\'.$config['driver'];
            static::$mailers[static::$active] = new $class($config);
        }

        return static::$mailers[static::$active];
    }

    public static function send($to=false, $subject=false, $content=false)
    {
        return static::getHandle()->send($to, $subject, $content);
    }

    public static function getResponse()
    {
        return static::getHandle()->getResponse();
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        
        $num = func_num_args();
        
        if ($num == 3) {
            return static::send(func_get_arg(0), func_get_arg(1), func_get_arg(2));
        }

        return $instance;    
    }
}

interface EmailInterface
{
    public function __construct($config);
    public function setAuth();
    public function from($from);
    public function to($to);
    public function subject($subject);
    public function content($content);
    public function type($type);
    public function send($to=false, $subject=false, $content=false);
    public function getResponse();
}
