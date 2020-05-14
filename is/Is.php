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
        return static::mobile($str, $local);
    }
    public static function mobile($str, $local = 'zh-CN')
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
    
    // 18位身份证校验(cn id no.) 末尾如果是X要大写
    public static function cnidno($id)
    {
        if ( ! preg_match('/^\d{18}$|^\d{17}X$/', $id) ) {
            return false;
        }
     
        $id = str_split($id);
        $x  = array(7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2);
        $y  = array(1,0,'X',9,8,7,6,5,4,3,2);
     
        $sum = 0;
        foreach ($x as $k=>$v) {
            $sum += $id[$k]*$v;
        }
        return (string)$y[$sum%11]===$id[count($id)-1];
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        return $instance;    
    }
}
