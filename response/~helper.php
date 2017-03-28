<?php

if (!function_exists('Response')) {
    function Response()
    {
        return call_user_func_array(PHPKit\PHPKit::get(strtolower(__FUNCTION__)), func_get_args());
    }
}

/**
 * 跳转
 * 
 * @param  string  $action 
 * @param  array|string  $params
 * @return string
 */
if (!function_exists('redirect')) {
    function redirect($url, $params=[]) {
        return Response()->redirect(url($url, $params));
    }
}
