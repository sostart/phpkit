<?php

namespace PHPKit;

class Is
{
    use LazySingletonTrait, LazyLinkTrait;
    
    public static function email($str)
    {
        return !(filter_var($str, FILTER_VALIDATE_EMAIL)===false);
    }

    public static function mobilePhone($str, $local = 'zh-CN')
    {
        if ($local=='zh-CN') {
            return preg_match('/^((1[3,5,8][0-9])|(14[5,7])|(17[0,1,6,7,8]))\d{8}$/', $str);
        }
        return false;
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        return $instance;    
    }
}
