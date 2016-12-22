<?php

if ( ! function_exists('ddd') ) {
    function ddd() {
        ob_start();

        $list = func_get_args();

        array_map(function ($x) {
            var_dump($x);
        }, $list);

        $output = ob_get_clean();

        if (!extension_loaded('xdebug')) {
            $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
            if ('cli'!==PHP_SAPI) {
                $output = '<pre>' . htmlspecialchars($output, ENT_QUOTES) . '</pre>';   
            }
        }

        echo $output;
    }
}
if ( ! function_exists('dd') ) {
    function dd()
    {
        call_user_func_array('ddd', func_get_args());
        die(1);        
    }
}

if ( ! function_exists('PHPKit') ) {
    function PHPKit()
    {
        return call_user_func_array(PHPKit\PHPKit::get(strtolower(__FUNCTION__)), func_get_args());
    }
}
if ( ! function_exists('App') ) {
    function App()
    {
        return call_user_func_array('PHPKit', func_get_args());
    }
}
