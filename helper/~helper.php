<?php

if (!function_exists('Helper')) {
    function Helper()
    {
        return call_user_func_array(PHPKit\PHPKit::get(strtolower(__FUNCTION__)), func_get_args());
    }
}



/**
 * 遍历文件文件夹, 可逐级遍历文件夹下所有文件及文件夹
 * 
 * 回调函数应接收一个参数 值为字符串 文件/文件夹名
 * 回调函数中返回false则跳出遍历, 返回-1则跳过(如果是文件夹)
 *
 * @param  string  $dir
 * @param  object  $callback
 * @return null
 */
if (!function_exists('eachdir')) {
    function eachdir()
    {
        return call_user_func_array(['PHPKit\Helper', __FUNCTION__], func_get_args());
    }
}
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



/**
 * 获取path / 生成path
 * 
 * 不传参数则获取当前path, 否则则是生成path
 *
 * @param  string  $action 
 * @param  array|string  $params
 * @return string
 */
if (!function_exists('path')) {
    function path() {
        return call_user_func_array(['PHPKit\Helper', __FUNCTION__], func_get_args());
    }
}
Helper()->register('path', function () {
    if (!func_num_args()) {
        return '/'.trim(rawurldecode( 
            isset($_SERVER['PATH_INFO']) ? ($_SERVER['PATH_INFO']?:'/') :
            ( (false !== $pos = strpos($_SERVER['REQUEST_URI'], '?')) ? substr($_SERVER['REQUEST_URI'], 0, $pos) : $_SERVER['REQUEST_URI'])
        ), '/');
    } else {
        $args = func_get_args();
        $action = $args[0]; $params = isset($args[1])?$args[1]:'';
        return '/' . ltrim($action, '/') . ($params ? '?'.http_build_query($params) : '');
    }
});

/**
 * 获取url / 生成url
 * 
 * 不传参数则获取当前url, 否则则是生成url
 *
 * @param  string  $action 
 * @param  array|string  $params
 * @return string
 */
if (!function_exists('url')) {
    function url() {
        return call_user_func_array(['PHPKit\Helper', __FUNCTION__], func_get_args());
    }
}
Helper()->register('url', function () {
    if (!func_num_args()) {
        return (($_SERVER["HTTPS"] == "on" || $_SERVER["SERVER_PORT"] == '443')?'https':'http').'://'.$_SERVER["SERVER_NAME"].(($_SERVER["SERVER_PORT"] != "80" || $_SERVER["SERVER_PORT"] != "443")?':'.$_SERVER["SERVER_PORT"]:'').$_SERVER["REQUEST_URI"];
    } else {
        return call_user_func_array(['Helper', 'path'], func_get_args());
    }
});
