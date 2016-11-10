<?php

if (!function_exists('eachdir')) {
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
    function eachdir($dir, $callback)
    {
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
    }
}

if (! function_exists('dd') && ! function_exists('ddd')) {
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed
     * @return void
     */
    function dd()
    {
        call_user_func_array('ddd', func_get_args());
        die(1);        
    }
    
    /**
     * 只dump变量不退出脚本
     *
     * @param  mixed
     * @return void
     */
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

if (! function_exists('PHPKit') && ! function_exists('App')) {
    /**
     * PHPKit
     *
     * 不传值         获取App实例
     * 传入单个数组   设置config
     * 传入单个字符串 获取字符串对应的实例
     *
     * @return mixed
     */
    function PHPKit()
    {
        return call_user_func_array(PHPKit\PHPKit::get(strtolower(__FUNCTION__)), func_get_args());
    }

    function App()
    {
        return call_user_func_array('PHPKit', func_get_args());
    }
}