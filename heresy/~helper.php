<?php

if (!function_exists('Heresy')) {
    /**
     * Heresy Helper
     *
     * 不传值         获取App实例
     * 传入单个数组   设置config
     * 传入单个字符串 获取字符串对应的实例
     *
     * @return mixed
     */
    function Heresy()
    {
        return call_user_func_array(PHPKit\PHPKit::get(strtolower(__FUNCTION__)), func_get_args());
    }
}