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
 * 获取uri / 生成uri
 * 
 * 不传参数则获取当前uri, 否则则是生成uri
 *
 * @param  string  $action 
 * @param  array|string  $params
 * @return string
 */
if (!function_exists('uri')) {
    function uri() {
        return call_user_func_array(['PHPKit\Helper', __FUNCTION__], func_get_args());
    }
}
Helper()->register('uri', function () {
    if (!func_num_args()) {
        return '/'.trim(rawurldecode( 
            isset($_SERVER['PATH_INFO']) ? ($_SERVER['PATH_INFO']?:'/') :
            ( (false !== $pos = strpos($uri, '?')) ? substr($_SERVER['REQUEST_URI'], 0, $pos) : $_SERVER['REQUEST_URI'])
        ), '/');
    } else {
        $args = func_get_args();
        $action = $args[0]; $params = isset($args[1])?$args[1]:'';
        return '/' . ltrim($action, '/') . ($params ? '?'.http_build_query($params) : '');
    }
});



/**
 * 跳转
 * 
 * @param  string  $action 
 * @param  array|string  $params
 * @return string
 */
if (!function_exists('redirect')) {
    function redirect() {
        return call_user_func_array(['PHPKit\Helper', __FUNCTION__], func_get_args());
    }
}
Helper()->register('redirect', function ($uri, $params='') {
    header('Location: '.uri($uri, $params));exit;
    //echo '<script>window.location.href="'.action($uri, $params).'"</script>';
});
