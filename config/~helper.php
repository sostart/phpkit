<?php

if (!function_exists('Config')) {
    function Config()
    {
        return call_user_func_array(App::get(strtolower(__FUNCTION__)), func_get_args());
    }
}