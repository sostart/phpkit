<?php

if (!function_exists('Storage')) {
    function Storage()
    {
        return call_user_func_array(App::get(strtolower(__FUNCTION__)), func_get_args());
    }
}