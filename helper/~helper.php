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
    $dir = rtrim($dir, '/\\').DIRECTORY_SEPARATOR;
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



/**
 * 生成验证码
 * 
 * type目前只有一种验证码
 *
 * @param  string  $type
 * @param  array   $config
 * @return string
 */
Helper()->register('vcode', function ($type=1, $config=[]) {
    //向浏览器输出图片头信息
    header('Content-type:image/jpeg');
    $width = $config['width']?:120;
    $height= $config['height']?:30;
    
    $img = imagecreatetruecolor($width, $height);//imagecreatetruecolor函数建一个真彩色图像
    
    //生成彩色像素    
    $colorBg=imagecolorallocate($img,rand(200,255),rand(200,255),rand(200,255));//背景     imagecolorallocate函数为一幅图像分配颜色
    //填充函数，xy确定坐标，color颜色执行区域填充颜色
    imagefill($img, 0, 0, $colorBg);

    //该循环,循环画背景干扰的点
    for($m=0;$m<=100;$m++){
        $pointcolor=imagecolorallocate($img,rand(0,255),rand(0,255),rand(0,255));//点的颜色
        imagesetpixel($img,rand(0,$width-1),rand(0,$height-1),$pointcolor);// 水平地画一串像素点
    }
    //该循环,循环画干扰直线
    for ($i=0;$i<=5;$i++){
        $linecolor=imagecolorallocate($img,rand(0,255),rand(0,255),rand(0,255));//线的颜色
        imageline($img,rand(0,$width),rand(0,$height),rand(0,$width),rand(0,$height),$linecolor);//画一条线段
    }

    $vcode= '';
    $arr = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','p','q','r','s','t','u','v','w','x','y','z','1','2','3','4','5','6','7','8','9');
    for($i=0;$i<5;$i++){
      $vcode.=$arr[rand(0,count($arr)-1)];
    }

    $colorString=imagecolorallocate($img,rand(10,100),rand(10,100),rand(10,100));
    //2种插入字符串字体的方式
    //imgettftext($img,字体大小（数字）,角度（数字）,rand(5,15),rand(30,35),$colorString,'字体样式的路径',$vcode);
    imagestring($img,5,rand(0,$width-36),rand(0,$height-15),$vcode,$colorString);
    imagejpeg($img);
    imagedestroy($img);

    return $vcode;
});
