<?php

if (!function_exists('Is')) {
    function Is()
    {
        return call_user_func_array(PHPKit\PHPKit::get(strtolower(__FUNCTION__)), func_get_args());
    }
}