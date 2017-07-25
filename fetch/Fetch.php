<?php

namespace PHPKit;

class Fetch
{
    use LazySingletonTrait, LazyLinkTrait;
    
    protected static $handle;
    protected static $setopt = [
        // ssl 不验证证书和HOST
        'CURLOPT_SSL_VERIFYPEER'=> false,
        'CURLOPT_SSL_VERIFYHOST' => false,
        
        // 设置超时时间
        'CURLOPT_TIMEOUT' => 60,
        
        // gzip
        'CURLOPT_ENCODING' => 'gzip,deflate',
		
        // 允许curl提交后,网页重定向  
        'CURLOPT_FOLLOWLOCATION' => true,
		
        // curl_exec 返回的字符串里包含header信息
		'CURLOPT_HEADER' => true,
		
		// 不直接输出返回信息
		'CURLOPT_RETURNTRANSFER' => true,
        
        // 追踪请求头信息(设置后 curl_getinfo 可以获取到请求时的头)
		'CURLINFO_HEADER_OUT' => true,

        // 设置后 curl_exec 400类错误 返回false而不是返回页面
		// 'CURLOPT_FAILONERROR' => true,
    ];

    protected static $response = '';
    protected static $responseHeader = '';

    protected static function API_setConfig($config)
    {
        static::setopt($config);
    }

    protected static function API_setopt($key, $val=null)
    {
        if ($val !== null) {
            static::$setopt[$key] = $val;
        } elseif (is_array($key)) {
            static::$setopt = $key;
        }
    }

    protected static function API_getopt($key=false)
    {
        if ($key) {
            return isset(static::$setopt[$key])?static::$setopt[$key]:'';
        } else {
            return static::$setopt;
        }
    }





    // 转换字符串cookie为数组
	public static function parseCookie($str)
    {
		$cookie = [];
		foreach(explode('; ', $str) as $v){
			$tmp = explode('=', $v);
			$cookie[$tmp[0]] = isset($tmp[1])?$tmp[1]:null;
		}
		return $cookie;
	}

    // 转换字符串header为数组
	public static function parseHeader($str)
    {
        $arr = explode("\r\n\r\n", $str);
        if (count($arr)>1) {
            $returns = [];
            foreach ($arr as $str) {
                $returns[] = static::parseHeader($str);
            }
            return $returns;
        } else {
            $headers = [];

            $rows = array_filter(explode("\r\n", $str));
            
            preg_match('/ ([0-9]{3})/', $code_raw = array_shift($rows), $matches);
            $headers['status'] = $code_raw; // $status = $matches[1];
            
            foreach ($rows as $row) {
                if ($row = trim($row)) {
                    list($k,$v) = explode(': ', $row, 2);   
                    if($k=='Set-Cookie') $v = static::parseCookie($v);
                    $headers[$k] = $v;   
                }
            }

            return [$headers];
        }
	}



    
    // 设置 header
    // 方式一 'key','val' 添加和修改
    // 方式二和三 ['key'=>val]  "key: val \r\n" 完全覆盖原来的设置会清空
    protected static function API_setHeader($key, $val=null)
    {
        $headers = static::getHeader();
        
        if ($val !== null) {
            $headers[$key] = $val;
        } elseif (is_array($key)) {
            $headers = $key;
        } elseif (is_string($key)) {
            $headers = [];
            $arr = array_map(function ($row) {
                return array_map('trim', explode(': ', $row));
            }, explode("\r\n", $key));

            foreach ($arr as $row) {
                $headers[$row[0]] = $row[1];
            }
        }
        
        // 避免重复设置cookie
        if (isset($headers['Cookie'])) {
            static::setCookie($headers['Cookie']);
            unset($headers['Cookie']);
        }

        $CURLOPT_HTTPHEADER = [];
        foreach ($headers as $k=>$v) {
            $CURLOPT_HTTPHEADER[] = $k.': '.$v;
        }

        $CURLOPT_HTTPHEADER && static::setopt('CURLOPT_HTTPHEADER', $CURLOPT_HTTPHEADER);
    }

    // 获取 header
    protected static function API_getHeader()
    {
        $headers = [];
        foreach(static::getopt('CURLOPT_HTTPHEADER')?:[] as $row) {
            $tmp = explode(': ', $row);
            $headers[$tmp[0]] = $tmp[1];
        }
        return $headers;
    }



    // 设置 cookie
    protected static function API_setCookie($key, $val=null)
    {
        $cookies = static::getCookie();

        if ($val !== null) {
            $cookies[$key] = $val;
        } elseif (is_array($key)) {
            $cookies = $key;
        } elseif (is_string($key)) {
            $cookies = [];
            $cookies = static::parseCookie($key);
        }
        
        $CURLOPT_COOKIE = '';
        foreach ($cookies as $k=>$v) {
            $CURLOPT_COOKIE .= $k.'='.$v.'; ';
        }
        $CURLOPT_COOKIE && static::setopt('CURLOPT_COOKIE', substr($CURLOPT_COOKIE,0,-2));
    }

    // 获取 cookie
    protected static function API_getCookie()
    {
        $cookies = static::getopt('CURLOPT_COOKIE')?:'';
        return $cookies?static::parseCookie($cookies):[];
    }


    protected static function API_request($url, $method, $params=false)
    {
        static::$handle && curl_close(static::$handle);

        static::$handle = curl_init();
        
        // url
		curl_setopt(static::$handle, CURLOPT_URL, $url);
        // method  GET POST HEAD DELETE PUT etc.
		curl_setopt(static::$handle, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($method=='POST' && $params) {
		    curl_setopt(static::$handle, CURLOPT_POSTFIELDS, $params);
        }

        foreach (static::getopt() as $key=>$val) {
            curl_setopt(static::$handle, constant($key), $val);
        }
        static::$response = curl_exec(static::$handle);
        
        if (static::$response == false) {
            return false;
        }
		
        static::$responseHeader = '';
        if (static::getopt('CURLOPT_HEADER')) {
            $headerSize = curl_getinfo(static::$handle, CURLINFO_HEADER_SIZE);
            static::$responseHeader = trim(substr(static::$response, 0, $headerSize), "\r\n");
            return substr(static::$response, $headerSize);
        }

        return static::$response;

    }

    protected static function API_get($url, $params=[])
    {
        $params = $params ? (strpos($url, '?') === false ? '?' : ''). http_build_query($params) : '';
        return static::request($url. $params, 'GET');
    }

    protected static function API_post($url, $params=[])
    {
        if ($params) {
            static::setopt('CURLOPT_SAFE_UPLOAD', true); // 禁用 @ 提交文件
        }
        return static::request($url, 'POST', $params);
    }
    

    protected static function API_getRequestInfo($opt=false)
    {
        if ($opt=='header') $opt = 'CURLINFO_HEADER_OUT';
        return $opt ? curl_getinfo(static::$handle, constant($opt)) : curl_getinfo(static::$handle);
    }
    
    protected static function API_getResponseRaw()
    {
        return static::$response;
    }

    protected static function API_getResponseHeader()
    {
        return static::parseHeader(static::$responseHeader);
    }

    protected static function API_getError()
    {
        return [curl_errno(static::$handle), curl_error(static::$handle)];
    }
}
