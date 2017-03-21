<?php

if (!function_exists('FastAPI')) {
    function FastAPI()
    {
        return call_user_func_array(PHPKit\PHPKit::get(strtolower(__FUNCTION__)), func_get_args());
    }
}