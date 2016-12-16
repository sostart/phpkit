<?php

if (!function_exists('Helper')) {
    function Helper()
    {
        return call_user_func_array(PHPKit\PHPKit::get(strtolower(__FUNCTION__)), func_get_args());
    }
}

if (!function_exists('eachdir')) {
    function eachdir()
    {
        return call_user_func_array(['PHPKit\Helper', __FUNCTION__], func_get_args());
    }
}
/**
 * 遍历文件文件夹 
 * 
 * 回调函数应接收一个参数 值为字符串文件/文件夹名
 * 回调函数中返回false则跳出遍历, 返回-1则跳过(如果是文件夹)
 *
 * @param  string  $dir
 * @param  object  $callback
 * @return null
 */
Helper()->register('eachdir', function ($dir, $callback) {
    $dir = rtrim($dir, '/\\').'/';
    if (is_dir($dir)) {
        if ($handle = opendir($dir)) {
            while (($file=readdir($handle)) !== false) {
                if ($file != '.' && $file != '..') {
                    
                    $file = $dir.$file;

                    if ( ($signal = $callback($file)) === false ) {
                        return false;
                    }

                    if (is_dir($file) && $signal!=-1) {
                        if (eachdir($file, $callback)===false) {
                            break;
                        }
                    }
                }
            }
        }
    }
});

if (!function_exists('uri')) {
    function uri() {
        return call_user_func_array(['PHPKit\Helper', __FUNCTION__], func_get_args());
    }
}
Helper()->register('uri', function () {
    return '/'.trim(rawurldecode( 
        isset($_SERVER['PATH_INFO']) ? ($_SERVER['PATH_INFO']?:'/') :
        ( (false !== $pos = strpos($uri, '?')) ? substr($_SERVER['REQUEST_URI'], 0, $pos) : $_SERVER['REQUEST_URI'])
    ), '/');
});
