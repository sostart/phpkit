<?php

if (!function_exists('AR')) {
    function AR()
    {
        return call_user_func_array(App::get(strtolower(__FUNCTION__)), func_get_args());
    }
}