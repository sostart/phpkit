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
            return preg_match('/^((1[3,5,8][0-9])|(14[5,7])|(17[0,1,3,5,6,7,8]))\d{8}$/', $str);
        }
        return false;
    }

    public static function url($str)
    {
        return !(filter_var($str, FILTER_VALIDATE_URL)===false);
    }
    
    // level 1 普通 字母或数字或英文符号6-20位
    public static function password($password, $level=1)
    {
        return preg_match('/^[\w`~!@#\$%\^&\*\(\)_\-\+\=\{\}\[\]\\\|:;"\'<>,\.\?\/]{6,20}$/', $password) ? true : false;
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        return $instance;    
    }
}
