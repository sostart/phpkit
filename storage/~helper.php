<?php

if (!function_exists('Storage')) {
    function Storage()
    {
        return call_user_func_array(PHPKit\PHPKit::get(strtolower(__FUNCTION__)), func_get_args());
    }
}