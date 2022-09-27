<?php

namespace {
    
    error_reporting(0);

    !defined('DS') && define('DS', DIRECTORY_SEPARATOR); // 定义 DS 分隔符缩写

    // 记录开始运行时间
    !defined('START_TIME') && define('START_TIME', $_SERVER['REQUEST_TIME_FLOAT']);

    // 设置未捕获的异常处理方法
    set_exception_handler(function($exception){
        echo ($exception->getCode()? $exception->getCode().'  ':'').$exception->getMessage(), '<br><br>', $exception->getFile().'  '.$exception->getLine();
        echo '<pre>', $exception->getTraceAsString(), '</pre>';
    });

    // 程序结束的处理
    register_shutdown_function (function(){
        if (Config('app.debug') && (php_sapi_name()!='cli')) {
            $exectime = round(microtime(true)-START_TIME, 3);
            $exectime = $exectime>1 ? $exectime.'s' : $exectime*1000 . 'ms';
            $response = json_decode(ob_get_contents(), true);
            if (!is_array($response)) {
                echo '<script>console.log(\''.Config('app.active-module').'-'.Config('app.run-mode').' : '.$exectime.'\');</script>';
            } else {
                ob_clean();
                $response['debug'][] = Config('app.active-module').'-'.Config('app.run-mode').' : '.$exectime;
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
        }
    });

    // 打印调试数据并结束脚本  dd(1,2,3)
    if (!function_exists('dd')) {
        function dd() {
            call_user_func_array('ddd', func_get_args());
            die(1);
        }
    }

    // 打印调试数据 ddd(1,2)
    if (!function_exists('ddd')) {
        function ddd()
        {
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
}

namespace PHPKit 
{

    // 列出目录下的目录
    function listdir($dir)
    {
        $return = [];

        if (is_dir($dir)) {
            if ($handle = opendir($dir)) {
                while (($file = readdir($handle)) !== false) {
                    if ($file != "." && $file != "..") {
                        if (is_dir($dir.DIRECTORY_SEPARATOR.$file)) {
                            $return[] = $file;
                        }
                    }
                }
                closedir($handle);
            }
        }

        return $return;
    }


    // 0 Debug,  1 Info, 2 Warn, 3 Error, 5 Fatal
    function wlog($msg, $level=0, $keep=false)
    {
        $phpkit = PHPKit::getInstance();

        $levelstr = ['DEBUG', 'INFO', 'WARN', 'ERROR', '', 'FATAL'];
        
        // 输出日志信息
        if ($level>=0) {
            if (php_sapi_name()=='cli') {
                echo ((strtoupper(substr(PHP_OS,0,3))==='WIN') ? iconv('UTF-8', 'GB2312', $msg) : $msg). " \r\n";
            } else {
                echo str_pad('',4096).$msg, '<br>';
                ob_flush();
                flush();
            }
        }
        
        // 存储日志信息
        if ($level>=2 || $keep) {
            $logfile = $phpkit->config->get('logdir').DIRECTORY_SEPARATOR.$phpkit->config->get('workerid').'.log';
            file_put_contents($logfile, str_pad($levelstr[$level], 5) .' ['. date('Y-m-d H:i:s') .'] '. $msg . "\r\n", FILE_APPEND);
        }
    }

    // -----------------------------------------------------------------------------

    function query($sql, $arr=[])
    {
        $phpkit = PHPKit::getInstance();
        
        $sth = $phpkit->db->prepare($sql);
        $rowcount = $arr?$sth->execute($arr):$sth->execute();
        
        $sql = trim($sql);
        if (substr($sql, 0, strpos($sql, ' '))=='select') {
            return $sth->fetchAll();    
        }
        
        return $rowcount;
    }

    function insert($table, $data)
    {
        $phpkit = PHPKit::getInstance();

        $prefix = $phpkit->config->get('database')['prefix'];
        
        $keys = $values = '';
        foreach (array_keys($data) as $key) {
            $keys .= '`'.$key.'`,';
            $values .= ':'.$key.',';
        }
        $keys = substr($keys,0,-1);
        $values = substr($values,0,-1);

        $sql = 'INSERT INTO `'.$prefix.$table.'`('.$keys.') VALUES('.$values.')';
            
        $newdata = [];
        foreach ($data as $key=>$val) {
            $newdata[':'.$key] = $val;
        }
        unset($data);

        $sth = $phpkit->db->prepare($sql);

        if ($sth->execute($newdata)) {
            return $phpkit->db->lastInsertId()?:true;
        }

        return false;
    }

    function find($table, $where, $order='', $limit=1)
    {
        $phpkit = PHPKit::getInstance();

        $prefix = $phpkit->config->get('database')['prefix'];
        
        if ($where) {
            if (is_array($where)) {
                $str = '';
                foreach ($where as $k=>$v) {
                    if (is_array($v)) {
                        $str .= " AND `$k`".$v[0]."?";
                        $wherearr[] = $v[1];
                    } else {
                        $str .= " AND `$k`=?";
                        $wherearr[] = $v;
                    }
                }
                $where = ' WHERE '.substr($str, 5);      
            } else if (is_string($where)) {
                $where = ' WHERE '.$where;
            }
            
        } else {
            $where = '';
        }

        if ($order) {
            $order = ' ORDER BY '.$order;
        }

        $xlimit = $limit ? ' LIMIT '.$limit : '';
        
        $sql = 'SELECT * FROM `'.$prefix.$table.'`'.$where.$order.$xlimit;
        
        if ($sth = $phpkit->db->prepare($sql)) {
        
            if ($wherearr) {
                $sth->execute($wherearr);
            } else {
                $sth->execute();
            }

            if ($rs = $sth->fetchAll()) {
                return ($limit==1) ? $rs[0] : $rs;
            }
        }

        return [];
    }

    function findAll($table, $where='', $order='', $limit=false)
    {
        $return = find($table, $where, $order, $limit);
        return ($return && ($limit==1)) ? [$return] : $return;
    }

    function update($table, $data, $where)
    {
        $phpkit = PHPKit::getInstance();

        if (!isset($data) || empty($data) || !isset($where)) {
            return false;
        }

        $prefix = $phpkit->config->get('database')['prefix'];
        
        $arr = [];
        
        if (is_array($data)) {
            $str = '';
            foreach ($data as $k=>$v) {
                $str .= '`'.$k.'`=?,';
                $arr[] = $v;
            }
            $set = $str ? ' SET '.substr($str, 0, -1) : '';
        }

        if (is_array($where)) {
            $str = '';
            foreach ($where as $k=>$v) {
                $str .= " AND `$k`=?";
                $arr[] = $v;
            }
            $where = substr($str, 5);
        }

        $where = ' WHERE '.$where;

        $sql = 'UPDATE `'.$prefix.$table.'`'.$set.$where;

        $sth = $phpkit->db->prepare($sql);

        return $sth->execute($arr);
    }

    function delete($table, $where)
    {
        $phpkit = PHPKit::getInstance();

        $prefix = $phpkit->config->get('database')['prefix'];

        $arr = [];

        if (is_array($where)) {
            $str = '';
            foreach ($where as $k=>$v) {
                $str .= " AND `$k`=?";
                $arr[] = $v;
            }
            $where = ' WHERE '.substr($str, 5);
        }

        $sql = 'DELETE FROM `'.$prefix.$table.'`'.$where;
        
        $sth = $phpkit->db->prepare($sql);

        return $sth->execute($arr);
    }

    // -----------------------------------------------------------------------------

    // 简单的认证与路由  $action => ['key'=>['action', callback]]
    function authAction($username, $password, $actions) {

        $cli = false;

        if (php_sapi_name()!='cli') {
            if (!($_SERVER['PHP_AUTH_USER']==$username) || !($_SERVER['PHP_AUTH_PW']==$password)) {
                header('WWW-Authenticate: Basic realm="needlogin"');
                header('HTTP/1.0 401 Unauthorized');exit;
            }
        } else {
            $cli = true;
        }

        if ($cli) {
            $action =  $GLOBALS['argv'][1];
            if (is_numeric($action)) {
                $index = 0;
                foreach ($actions as $key=>$val) {
                    $index++;
                    if ($action == $index) {
                        $action = $key; break;
                    }
                }
            }
        } else {
            $action = isset($_GET['action'])?$_GET['action']:false;
        }

        if (!$action) {
            $index = 0;
            foreach ($actions as $key=>$val) {
                $index++;
                if ($val[0]) {
                    if ($cli) {
                        wlog($index.' '.$val[0]);
                    } else {
                        echo '<a target="_blank" href="index.php?action='.$key.'">'.$val[0].'</a><br />';
                    }
                }
            }
        } elseif (is_string($action) && isset($actions[$action])) {
            call_user_func($actions[$action][1]);
        }
    }

    class HTTP
    {
        private $handle;
        private $_response_headers=[];

        public function __construct()
        {
            $this->handle = curl_init();
        }
        
        // 解压gzip
        public static function gzdecode($data)
        {
            if (function_exists('gzdecode')) {
                return gzdecode($data);
            } else {
                return gzinflate(substr($data,10,-8));            
            }
        }

        // 设置头信息
        public function headers($headers)
        {
            // Accept-Encoding
            // curl_setopt($this->handle, CURLOPT_ENCODING, 'gzip, deflate, sdch');
            // $headers[] = 'Accept-Encoding: gzip, deflate, sdch';

            // Referer
            // curl_setopt($this->handle, CURLOPT_HTTPHEADER, 'http://www.sostart.net/');
            // $headers[] = 'Referer: http://www.sostart.net/';

            // 来源IP
            // $headers[] = ['CLIENT-IP: 88.88.88.88', 'X-FORWARDED-FOR: 88.88.88.88'];

            // Cookie
            // curl_setopt($this->handle, CURLOPT_COOKIE, 'a=1;b=2');
            // $headers[] = 'Cookie: a=1;b=2';

            // User-Agent
            // curl_setopt($this->handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.116 Safari/537.36');
            // $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.116 Safari/537.36';

            if (is_string($headers)) {
                $headers = array_filter(array_map('trim', explode("\r\n", $headers)));
            }

            curl_setopt($this->handle, CURLOPT_HTTPHEADER, $headers);

            return $this;
        }

        public function cookies($cookies)
        {
            curl_setopt($this->handle, CURLOPT_COOKIE, $cookies);
            return $this;
        }
        
        public function get($url, $param=[])
        {
            $param = $param?(strpos($url, '?') === false ? '?' : ''). http_build_query($param):'';
            return $this->request($url. $param, 'GET');
        }

        public function post($url, $param=[])
        {
            if ($param) {
                // 禁用 @ 提交文件
                curl_setopt($this->handle, CURLOPT_SAFE_UPLOAD, true);
                // 
                curl_setopt($this->handle, CURLOPT_POSTFIELDS, $param);
            }
            return $this->request($url, 'POST');
        }

        public function request($url, $method)
        {
            // url
            curl_setopt($this->handle, CURLOPT_URL, $url);
            
            // ssl 不验证证书和HOST
            curl_setopt($this->handle, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($this->handle, CURLOPT_SSL_VERIFYHOST, FALSE);
            
            //http://curl.haxx.se/ca/cacert.pem
            //curl_setopt($this->handle, CURLOPT_SSL_VERIFYPEER, true); ;
            //curl_setopt($this->handle, CURLOPT_CAINFO, dirname(__FILE__).'/cacert.pem');

            // 设置超时时间
            curl_setopt($this->handle, CURLOPT_TIMEOUT, 60);
            
            // GET POST HEAD DELETE PUT etc.
            curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $method);
            
            // 允许curl提交后,网页重定向  
            curl_setopt($this->handle, CURLOPT_FOLLOWLOCATION, 1); 
            
            // curl_exec 返回的字符串里包含header信息
            curl_setopt($this->handle, CURLOPT_HEADER, 1);
            
            // 不直接输出返回信息
            curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1);
            
            // 追踪请求头信息(设置后 curl_getinfo 可以获取到请求时的头)
            curl_setopt($this->handle, CURLINFO_HEADER_OUT, 1);

            // 设置后 curl_exec 400类错误 返回false而不是返回页面
            //curl_setopt($this->handle, CURLOPT_FAILONERROR, 1);
            
            $html = curl_exec($this->handle);
            
            if ($html == false) {
                // curl_errno 获取错误信息
                return false;
            }

            preg_match("/([\W\S]*)\r\n\r\n([\W\S]*)/", $html, $matches);

            $this->parse_response_header($matches[1]);
            
            if (preg_match('/Content-Encoding(.*)gzip/',$matches[1])) {
                return HTTP::gzdecode($matches[2]);
            } else {
                return $matches[2];
            }
        }
        
        private function parse_response_header($response_headers)
        {
            $this->_response_headers = [];

            $response_header_lines = explode("\r\n", $response_headers);
            
            preg_match('/ ([0-9]{3})/', $code_raw = array_shift($response_header_lines), $matches);
            $this->_response_headers['code'] = $matches[1];
            $this->_response_headers['code_raw'] = $code_raw;
            
            foreach ($response_header_lines as $header_line) {   
                list($k,$v) = explode(': ',$header_line,2);   
                if($k=='Set-Cookie') $v = $this->parseCookie($v);
                $this->_response_headers[$k] = $v;   
            }
        }

        private static function parseCookie($str)
        {
            $cookie = array();
            foreach(explode(';', $str) as $v){
                $tmp = explode('=', $v);
                $cookie[$tmp[0]] = $tmp[1];
            }
            return $cookie;
        }
        
        // 调试请求信息
        public function requestInfo()
        {
            // curl_getinfo($this->handle, CURLINFO_HEADER_OUT);  返回发送的头
            return curl_getinfo($this->handle);
        }

        // 获取返回的头信息
        public function responseHeaders()
        {
            return $this->_response_headers;
        }

        public function getError()
        {
            return [curl_errno($this->handle), curl_error($this->handle)];
        }
        
        public function __destruct()
        {
            curl_close($this->handle);
        }
    }

    class Ticker
    {
        private $tickers = [];

        public function start($name, $time=false)
        {
            $this->tickers[$name]['start'] = $time ?: microtime(true);
        }

        public function stop($name, $time=false)
        {
            $this->tickers[$name]['stop'] = $time ?: microtime(true);
            return $this->tickers[$name]['stop']-$this->tickers[$name]['start'];
        }
    }

    use Exception;

    trait LazyTrait
    {
        use LazySingletonTrait, LazyLinkTrait;
    }

    trait LazySingletonTrait
    {
        /**
         * 工具唯一实例
         *
         * @var object
         */
        static $instance;

        /**
         * 不能在外部实例化, 需要调用接口getInstance获得工具实例, 保证全局有且只有一个工具实例
         *
         * @return void
         */    
        protected function __construct()
        {
        
        }

        /**
         * 工具在首次被调用时执行此方法
         *
         * @return void
         */  
        protected static function init()
        {

        }

        /**
         * 获取工具唯一实例
         * 
         * 首次调用工具, 自动执行工具初始化函数(init), 并激活工具(如果工具已注册并设置了激活方法 PHPKit::registerTools)
         *
         * @api
         *
         * @return object
         */
        public static function getInstance()
        {
            if (!static::$instance) {
                
                static::$instance = new static;
                static::init();
                PHPKit::get(__CLASS__);
            
            }

            return static::$instance;
        }

        /**
         * 工具实例可以直接当函数使用, 默认返回工具实例
         *
         * @return mixed
         */
        public function __invoke()
        {
            $instance = static::getInstance();
            return $instance;
        }
    }

    trait LazyLinkTrait
    {
        public function __call($name, $arguments)
        {
            return static::__callStatic($name, $arguments);
        }

        public static function __callStatic($name, $arguments)
        {
            $instance = static::getInstance();
            
            $name = 'API_'.$name;

            if (!method_exists($instance, $name)) {
                throw new Exception(__CLASS__. " $name ". ' 未定义');
            }
            
            return is_null( $return = call_user_func_array([$instance, $name], $arguments) ) ? $instance: $return; 
        }
    }


    trait DI
    {

        protected static $container = [];

        protected static $singleton = [];
        
        protected static $alias = [];

        public function __set($name, $value)
        {
            throw new Exception('不允许的操作');
        }

        public function __isset($name)
        {
            if (isset(static::$alias[$name])) {
                return $this->__isset(static::$alias[$name]);
            }
            return isset(static::$singleton[$name]) || isset(static::$container[$name]);
        }

        public function __get($name)
        {
            return static::get($name);
        }

        protected static function API_get($name)
        {
            if (isset(static::$alias[$name])) {
                return static::get(static::$alias[$name]);
            } elseif (isset(static::$singleton[$name])) {
                return static::$singleton[$name];
            } elseif (isset(static::$container[$name])) {
                static::$singleton[$name] = false; // 防止在call_user_func中重复调用
                return static::$singleton[$name] = call_user_func(static::$container[$name]);
            }
            return false;
        }

        protected static function API_set($list, $value=null)
        {
            if (is_string($list) && !is_null($value)) {
                $list = [$list=>$value];
            }

            if (is_array($list)) {
                foreach ($list as $name=>$closure) {
                    if (is_callable($closure)) {
                        static::$container[$name] = $closure;
                    } else {
                        static::$singleton[$name] = $closure;
                    }
                }
            }
        }

        protected static function API_alias($list, $value=null)
        {
            if (is_string($list) && !is_null($value)) {
                $list = [$list=>$value];
            }

            if (is_array($list)) {
                foreach ($list as $name=>$aliases) {
                    foreach ((array)$aliases as $alias) {
                        static::$alias[$alias] = $name;
                    }
                }
            }
        }
    }

    trait Loader
    {
        protected static $classAlias = [];
        protected static $classMap = [];
        protected static $psr4 = ['PHPKit\\'=>__DIR__];
        
        protected static function autoload($class)
        {
            // 别名类自动载入
            if (isset(static::$classAlias[$class])) {
                return class_alias(static::$classAlias[$class], $class);
            }
            
            // classmap
            if (isset(static::$classMap[$class])) {
                return includeFile(static::$classMap[$class]);
            }

            // psr4
            $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';
            foreach (static::$psr4 as $prefix=>$dir) {
                if (strncmp($prefix, $class, strlen($prefix))===0) {
                    if (is_file($file = $dir.DIRECTORY_SEPARATOR.substr($logicalPathPsr4, strlen($prefix)))) {
                        return includeFile($file);
                    }
                }
            }
        }

        protected static function API_loadFiles($files)
        {
            foreach ((array)$files as $file) {
                includeFile($file);
            }
        }

        protected static function API_registerDirs($list)
        {
            foreach ($list as $namespace=>$dir) {
                static::get('loader')->addPsr4($namespace, $dir, true);
            }
        }

        protected static function API_addClassMap(array $classMap)
        {
            static::$classMap = array_merge(static::$classMap, $classMap);
        }

        protected static function API_setPsr4($prefix, $paths, $prepend = false)
        {
            if ($prepend) {
                static::$psr4 = array_reverse(static::$psr4);
                static::$psr4[$prefix] = $paths;
                static::$psr4 = array_reverse(static::$psr4);
            } else {
                static::$psr4[$prefix] = $paths;
            }
        }

        protected static function API_classAlias($alias, $target=false)
        {
            if (is_array($alias)) {
                static::$classAlias = array_merge(static::$classAlias, $alias);
            } else {
                static::$classAlias = array_merge(static::$classAlias, [$target=>$alias]);
            }
        }
    }

    function includeFile($file)
    {
        include_once $file;
    }

    class PHPKit
    {
        use LazyTrait, DI, Loader;

        protected static $tools = [];
        
        protected static function init()
        {
            spl_autoload_register(['PHPKit\PHPKit', 'autoload']);
            static::set('loader', static::getInstance())->registerTools(['PHPKit'])->alias('phpkit', 'app');
            static::classAlias(['PHPKit\App' => static::class]);
        }

        protected static function API_registerTools(array $tools, $helper=true)
        {
            $basedir = dirname(__DIR__);
            
            $consthelper = $helper;

            foreach ((array)$tools as $tool=>$closure) {
                
                $helper = $consthelper;

                // 注册工具未设置实例化时的闭包 如 ['Response', 'Config']
                if (is_numeric($tool) && is_string($closure)) {
                    
                    $tool = $closure;

                    $closure = function () use ($tool) {
                        return call_user_func([class_exists($tool)?$tool:'PHPKit\\'.$tool, 'getInstance']);
                    };
                }
                
                if (is_array($closure)) {
                    $dir = ($closure[0]?:$basedir).DIRECTORY_SEPARATOR.strtolower($tool);
                    if (isset($closure[2])) $helper = $closure[2];
                    if (isset($closure[1]) && $closure[1]) $closure = $closure[1];
                } else {
                    $dir = $basedir.DIRECTORY_SEPARATOR .strtolower($tool);    
                }
                
                // 屏蔽文件载入 knife.php 包含了所有文件
                //$file = $dir.DIRECTORY_SEPARATOR.$tool.'.php';

                //if (is_dir($dir) && is_file($file)) {

                    static::$tools[strtolower($tool)] = $tool;

                    static::set(strtolower($tool), $closure); // di
                    static::alias(strtolower($tool), 'PHPKit\\'.$tool); // di 别名

                    //static::get('loader')->addClassMap(['PHPKit\\'.$tool=>$file]);
                    //static::get('loader')->setPsr4('PHPKit\\'.$tool.'\\', $dir);

                    //if ($helper && ($file = $dir.DIRECTORY_SEPARATOR.'~helper.php') && file_exists($file)) {
                        //includeFile($file);
                    //}

                //} else {
                    //throw new Exception($tool.' 工具不存在');
                //}
            }
        }

        protected static function API_loadTools($tools)
        {
            foreach ((array)$tools as $tool) {
                static::get(strtolower($tool));
            }
        }

        public function __invoke()
        {
            $instance = static::getInstance();
            
            $num = func_num_args();

            if ($num == 1) {
                $arg = func_get_arg(0);
                if (is_array($arg)) {
                    foreach ($arg as $k=>$v) {
                        $instance->config->set($k, $v);
                    }
                } elseif (is_string($arg)) {
                    return $instance->get($arg);
                }
            } elseif ($num == 2) {
                static::set(func_get_arg(0), func_get_arg(1));
            }

            return $instance;
        }
    }

    class FastRoute
    {
        const FOUND = 200;
        const NOT_FOUND = 404;

        protected $map = [];
        protected $regulars = [];

        public function regular($regular, $callable, $params=[])
        {
            $this->regulars[$regular] = [$callable, $params];
        }

        public function add($regularSugar, $callable)
        {
            if (preg_match_all('~{([^}?]+)\??}~i', $regularSugar, $matches)===0) {
                $this->map[$regularSugar] = $callable;
            } else {
                $regular = preg_replace(['~/{[^}]+\?}~i', '~{[^}]+}~i'], ['(/[^/]+)?', '([^/]+)'], $regularSugar);
                $this->regular('~^'.$regular.'$~i', $callable, $matches[1]);
            }
        }

        public function match($uri)
        {
            if (isset($this->map[$uri])) {
                return [FastRoute::FOUND, $this->map[$uri], []];
            }

            foreach ($this->regulars as $regular=>$row) {
                if (preg_match_all($regular, $uri, $matches)) {
                    for ($i=1; $i<count($matches); $i++) {
                        if ($matches[$i][0]!=='') {
                            $params[$row[1][$i-1]] = trim($matches[$i][0], '/');
                        }
                    }
                    
                    return [FastRoute::FOUND, $row[0], isset($params)?$params:[]];
                }
            }

            return [FastRoute::NOT_FOUND, [], []];
        }
    }

    class Route
    {
        const FOUND = FastRoute::FOUND;
        const NOT_FOUND = FastRoute::NOT_FOUND;
        
        protected static $route;

        protected static $uri = '/';
        protected static $alias = [];

        protected static $group = '';
        protected static $middlewares = [];

        protected static $routeGroups = [];

        protected static $dispatcher = false;

        use LazySingletonTrait, LazyLinkTrait;
        
        protected static function init()
        {
            static::$route = static::fastRoute();
        }

        protected static function API_fastRoute()
        {
            return new FastRoute;
        }

        protected static function API_setDispatcher($dispatcher)
        {
            static::$dispatcher = $dispatcher;
        }

        protected static function API_group($uri, $middleware, $callable=null)
        {
            if (is_null($callable)) {
                $callable = $middleware;
                $middleware = [];
            }

            $uri = '/'.trim($uri, '/');
            $uri = '/'.trim(static::$group.$uri, '/');

            static::$routeGroups[$uri] = [$middleware, $callable];
        }

        protected static function expandGroup($fulluri)
        {
            $routeGroups = static::$routeGroups;
            static::$routeGroups = [];
            foreach ($routeGroups as $uri=>$arr) {
                if (preg_match('~^'.$uri.'~i', $fulluri)) {

                    $middleware = $arr[0];
                    $callable = $arr[1];

                    $group = static::$group;
                    static::$group = '/'.trim($uri, '/');
                    
                    $middlewares = static::$middlewares;
                    static::$middlewares = array_merge(static::$middlewares, (array)$middleware);
                    
                    call_user_func($callable);
                    
                    // 在展开时有新增的路由组,则继续展开
                    if (static::$routeGroups) {
                        static::expandGroup($fulluri);
                    }

                    static::$middlewares = $middlewares;
                    static::$group = $group;
                }
            }
            static::$routeGroups = $routeGroups;
        }
        
        protected static function API_any($uri, $middleware, $callable=null)
        {
            if (is_null($callable)) {
                $callable = $middleware;
                $middleware = [];
            }

            $uri = (rtrim('/'.trim(static::$group.'/'.trim($uri, '/'), '/'), '/')?:'/');

            static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
        }
        
        protected static function API_match()
        {
            $instance = static::getInstance();
            call_user_func_array([$instance, 'register'], func_get_args());
        }

        protected static function API_register($methods, $uri, $middleware, $callable=null)
        {
            $instance = static::getInstance();
            foreach ($methods as $method) {
                call_user_func_array([$instance, $method], [$uri, $middleware, $callable]);
            }
        }

        protected static function API_get($uri, $middleware, $callable=null)
        {
            if (is_null($callable)) {
                $callable = $middleware;
                $middleware = [];
            }

            $uri = 'GET '.(rtrim('/'.trim(static::$group.'/'.trim($uri, '/'), '/'), '/')?:'/');

            static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
        }

        protected static function API_post($uri, $middleware, $callable=null)
        {
            if (is_null($callable)) {
                $callable = $middleware;
                $middleware = [];
            }

            $uri = 'POST '.(rtrim('/'.trim(static::$group.'/'.trim($uri, '/'), '/'), '/')?:'/');

            static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
        }

        protected static function API_patch($uri, $middleware, $callable=null)
        {
            if (is_null($callable)) {
                $callable = $middleware;
                $middleware = [];
            }

            $uri = 'PATCH '.(rtrim('/'.trim(static::$group.'/'.trim($uri, '/'), '/'), '/')?:'/');

            static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
        }

        protected static function API_put($uri, $middleware, $callable=null)
        {
            if (is_null($callable)) {
                $callable = $middleware;
                $middleware = [];
            }

            $uri = 'PUT '.(rtrim('/'.trim(static::$group.'/'.trim($uri, '/'), '/'), '/')?:'/');

            static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
        }

        protected static function API_delete($uri, $middleware, $callable=null)
        {
            if (is_null($callable)) {
                $callable = $middleware;
                $middleware = [];
            }

            $uri = 'DELETE '.(rtrim('/'.trim(static::$group.'/'.trim($uri, '/'), '/'), '/')?:'/');

            static::$route->add($uri, [array_merge(static::$middlewares, (array)$middleware), $callable]);
        }

        protected static function API_dispatch($uri=false, $method=false, $dispatcher=false)
        {
            $method = $method?:(isset($_REQUEST['_method'])?strtoupper($_REQUEST['_method']):(isset($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:'GET'));
            $uri = $uri?:rawurldecode( 
                isset($_SERVER['PATH_INFO']) ? ($_SERVER['PATH_INFO']?:'/') :
                ( isset($_SERVER['REQUEST_URI']) ? ((false !== $pos = strpos($_SERVER['REQUEST_URI'], '?')) ? substr($_SERVER['REQUEST_URI'], 0, $pos) : $_SERVER['REQUEST_URI']) : '/')
            );

            static::$uri = $uri = '/'.trim($uri, '/');
            
            if (isset(static::$alias[static::$uri])) {
                static::$uri = $uri = static::$alias[static::$uri];
            }

            // 首先解开路由组
            static::expandGroup($uri);
            
            $routeInfo = static::$route->match($uri);
            if ($routeInfo[0] == FastRoute::NOT_FOUND) {
                $uri = strtoupper($method).' '.$uri;
                $routeInfo = static::$route->match($uri);
            }

            if ($dispatcher===false) {
                $dispatcher = static::$dispatcher;
            }

            return is_callable($dispatcher)?call_user_func($dispatcher, $routeInfo):$routeInfo;
        }

        public static function getUri() {
            return static::$uri;
        }

        protected static function API_alias($original, $alias) {
            static::$alias[$alias] = $original;
        }

        public function __invoke()
        {
            $instance = static::getInstance();
            return $instance;
        }
    }

    class Config
    {
        use LazySingletonTrait, LazyLinkTrait;

        protected static $__storage = [];
        
        // 从文件中载入配置
        protected static function API_load($file)
        {
            if (is_file($file)) {
                
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $filename  = pathinfo($file, PATHINFO_FILENAME);

                if ($extension=='php') {
                    static::$__storage = array_merge(static::$__storage, [$filename=>include $file]);
                }
            }
        }

        // get('database') get('database.default')
        public static function get($name)
        {
            $instance = static::getInstance();

            $return = static::$__storage;

            foreach (explode('.', $name) as $key) {
                if (isset($return[$key])) {
                    $return = $return[$key];
                } else {
                    return null;
                }
            }

            return $return;
        }

        public static function all()
        {
            $instance = static::getInstance();
            return static::$__storage;
        }

        // set('a', 123) set('b.c', 456) set(['a'=>123, 'b.c'=>456])
        public static function set($name, $value=null)
        {
            $instance = static::getInstance();

            if (is_array($name)) {
                foreach ($name as $k=>$v) {
                    static::set($k, $v);
                }
            } else {
                $return = & static::$__storage;
                foreach (explode('.', $name) as $key) {
                    $return = & $return[$key];
                }
                return $return = $value;
            }
        }

        public function __get($name)
        {
            return static::get($name);
        }
        
        public function __set($name, $value=null)
        {
            return static::set($name, $value);
        }

        public function __invoke()
        {
            $instance = static::getInstance();

            if ($num = func_num_args()) {
                if ($num == 1) {
                    $arg = func_get_arg(0);
                    if (is_array($arg)) {
                        return $instance->set($arg);
                    } else {
                        return $instance->get($arg);
                    }
                } else {
                    return $instance->set(func_get_arg(0), func_get_arg(1));
                }
            }

            return $instance;    
        }
    }

    class Heresy
    {
        use LazySingletonTrait, LazyLinkTrait;

        protected static $bewitch = [];

        // 被bewitch的名字空间 自动载入类的时候会在这些名字空间下搜索
        // 存在多个同名类时 加载最先找到的 (这里顺序靠前的会优先加载)
        protected static $searchNamespace  = [];

        public static function init()
        {
            spl_autoload_register(['PHPKit\Heresy', 'autoload']);
        }

        protected static function autoload($class)
        {
            // 如果类的名字空间 在searchNamespace内 则跳出
            foreach (static::$searchNamespace as $namespace) {
                if (strpos($class, $namespace)===0) {
                    return false;
                }
            }
            
            // 被蛊惑的名字空间下  加载失败的类 从searchNamespace下尝试加载
            $class = trim($class, '\\');
            $root = ($pos = strpos($class, '\\')) ? substr($class, 0, $pos) : '\\';
            if ( (isset(static::$bewitch['\\']) && static::$bewitch['\\']) || (isset(static::$bewitch[$root]) && static::$bewitch[$root]) ) {
                foreach (static::$searchNamespace as $namespace) {
                    $name = ($pos = strrpos($class, '\\')) ? substr($class, $pos+1) : $class;
                    $search_class = $namespace . $name;
                    if ((class_exists($search_class) || trait_exists($search_class)) && class_alias($search_class, $class)) {
                        break;
                    }
                }
            }

            return true;
        }

        protected static function API_searchNamespace($list)
        {
            static::$searchNamespace = array_merge((array)$list, static::$searchNamespace);
        }

        protected static function API_bewitch($namespace)
        {
            foreach ((array)$namespace as $one) {
                $one = trim($one, '\\')?:'\\';
                $root = ($pos = strpos($one, '\\')) ? substr($one, 0, $pos) : $one; // 从根域开始
                static::$bewitch[$root] = true;
            }
        }

        public function __invoke()
        {
            $instance = static::getInstance();
            return $instance;
        }
    }

    class Response
    {
        use LazySingletonTrait, LazyLinkTrait;
        
        protected static $responseType = 'html'; // html => text/html json => application/json
        protected static $response = ['status'=>200, 'message'=>'OK', 'content'=>''];
        protected static $redirect;
        
        protected static function API_redirect($url, $status=302)
        {
            static::$redirect = $url;
        }

        protected static function API_status($status=null)
        {
            if (!is_null($status)) {
                static::$response['status'] = $status;
            } else {
                return static::$response['status'];
            }
        }

        protected static function API_message($message=null)
        {
            if (!is_null($message)) {
                static::$response['message'] = $message;
            } else {
                return static::$response['message'];
            }
        }

        protected static function API_content($content=null)
        {
            if (!is_null($content)) {
                static::$response['content'] = $content;
            } else {
                return static::$response['content'];
            }
        }

        protected static function API_header($key, $val='')
        {
            if (is_array($key)) {
                foreach ($key as $k=>$v) {
                    static::header($k, $v);
                }
            } else {
                static::$response['headers'][$key] = $val;
            }
        }

        protected static function API_json($callback=null)
        {
            
            static::type('json');

            if (!is_null($callback)) {
                if ($callback===false) {
                    static::type('html');
                } else {
                    static::callback($callback);
                }
            }
        }

        protected static function API_type($type)
        {
            static::$responseType = $type;
        }

        protected static function API_callback($callback)
        {
            static::$response['jsonp_callback'] = $callback;
        }

        public function __toString()
        {
            if (static::$redirect) {
                header('Location: '.static::$redirect);
            } else {
                if (static::$responseType=='json') {
                    header('Content-type: application/json');
                    $response = json_encode([
                        'status'=>static::$response['status'], 'message'=>static::$response['message'], 'data'=>static::$response['content']
                    ], JSON_UNESCAPED_UNICODE);

                    if (isset(static::$response['jsonp_callback'])) {
                        return static::$response['jsonp_callback'].'('.$response.')';
                    } else {
                        return $response;
                    }
                } else {
                    header('HTTP/1.1 '.static::status().' '.static::message());
                    return (string)static::content();
                }
            }
        }

        public function __invoke()
        {
            $instance = static::getInstance();
            if ($num = func_num_args()) {
                if ($num == 1) {
                    $content = func_get_arg(0);
                    static::content($content);
                } elseif ($num == 2) {
                    $content = func_get_arg(0);
                    $status = func_get_arg(1);
                    static::content($content)->status($status);
                }
            }
            return $instance;    
        }
    }

    class View
    {
        use LazySingletonTrait, LazyLinkTrait;
        
        use BladeTrait;

        protected static $dir = [];

        protected static $share = [];

        protected static function API_setViewsDir($dir)
        {
            static::$dir = array_map(function ($v) {
                return rtrim($v, '\/');
            }, (array)$dir);
        }

        protected static function API_addViewsDir($dir)
        {
            foreach ((array)$dir as $v) {
                array_unshift(static::$dir, rtrim($v, '\/'));
            }
        }

        protected static function API_removeViewsDir($dir)
        {
            if ($index = array_search($dir, static::$dir)) {
                unset(static::$dir[$index]);
            }
        }

        protected static function API_share($key, $val=null)
        {

            if (is_string($key)) {

                if ($val === null) {
                    return static::$share[$key];
                }
                
                $key = [$key=>$val];
            }

            foreach ($key as $k=>$v) {
                static::$share[$k] = $v;
            }
        }
        
        protected static function renderFile($file, $data)
        {
            return call_user_func(function () {
                is_array(func_get_arg(1)) && extract(func_get_arg(1));
                ob_start();
                ob_implicit_flush(false);
                include func_get_arg(0);
                return ob_get_clean();
            }, $file, $data);
        }

        protected static function renderBlade($file, $data)
        {
            if (is_dir(static::$compileDir)) {

                $compileFile = static::$compileDir.DIRECTORY_SEPARATOR.sha1($file);

                // 存在编译缓存
                if (file_exists($compileFile) && filemtime($compileFile)>filemtime($file)) {
                    return static::renderFile($compileFile, $data);
                }

                $compile = static::compile(file_get_contents($file));
                file_put_contents($compileFile, $compile);
                return static::renderFile($compileFile, $data);

            } else {

                $compile = static::compile(file_get_contents($file));
                return call_user_func(function () {
                    is_array(func_get_arg(1)) && extract(func_get_arg(1));
                    ob_start();
                    ob_implicit_flush(false);
                    eval('?>'.func_get_arg(0));
                    return ob_get_clean();
                }, $compile, $data);
            
            }
        }

        protected static function API_render($view, $data=[])
        {
            $data = array_merge(static::$share, $data);

            foreach (static::$dir as $dir) {

                $file = $dir.DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, $view);
                
                foreach (['.php', '.html', '.view'] as $ext) {
                    if (file_exists($file.$ext)) {
                        return static::renderFile($file.$ext, $data);
                    } elseif (file_exists($file.'.blade'.$ext)) {
                        return static::renderBlade($file.'.blade'.$ext, $data);
                    }
                }
            }

            return '';
        }

        public function __invoke()
        {
            $instance = static::getInstance();
            return func_num_args() ? call_user_func_array(['static', 'render'], func_get_args()) : $instance;
        }
    }

    trait BladeTrait
    {
        protected static $sections = [];
        protected static $section = '';
        protected static $compileDir = false;
        
        protected static function API_setCompileDir($dir)
        {
            static::$compileDir = $dir;
        }

        protected static function API_section($section)
        {
            ob_start();
            ob_implicit_flush(false);
            return static::$section = $section;
        }

        protected static function API_endsection()
        {
            return static::$sections[static::$section] = ob_get_clean();
        }

        protected static function API_hasSection($section)
        {
            return isset(static::$sections[$section]);
        }

        protected static function API_yield($section)
        {
            return isset(static::$sections[$section])? static::$sections[$section] : '';
        }

        protected static function compileFile($view)
        {
            foreach (static::$dir as $dir) {
                $file = $dir.DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, $view).'.php';
                $blade = $dir.DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, $view).'.blade.php';
                if (is_file($file)) {
                    return file_get_contents($file);
                } elseif (is_file($blade)) {
                    return static::compile(file_get_contents($blade));
                }
            }
        }

        protected static function compile($template)
        {
            $extends = [];
            if (preg_match_all('/@extends\([\'"](.*)[\'"]\)[\r\n]?/', $template, $matches)) {
                $extends = $matches[1];
            }
                            
            $template = preg_replace(
                [
                    '/(@(extends|section|endsection|yield|include)(\(.+\))?(\r\n)?)[\r\n]+(@(extends|section|endsection|yield|include))/',
                    '/@extends\([\'"](.*)[\'"]\)/',
                    '/@section\([\'"](.*)[\'"]\)/',
                    '/@endsection/',
                    '/@yield\([\'"](.*?)[\'"]\)/',
                ],

                [
                    '$1$5',
                    '',
                    '<?php View::section(\'$1\'); ?>',
                    '<?php View::endsection(); ?>',
                    '<?php echo View::yield(\'$1\'); ?>'
                ],
                
                preg_replace_callback('/@include\([\'"](.*)[\'"]\)/', function ($matches) {
                    return static::compileFile($matches[1]);
                }, $template)
            );
            
            foreach ($extends as $extendsfile) {
                $template .= static::compileFile($extendsfile);
            }

            return $template;
        }
    }
}

namespace 
{
    if (!function_exists('Config')) {
        function Config()
        {
            return call_user_func_array(\PHPKit\PHPKit::get(strtolower(__FUNCTION__)), func_get_args());
        }
    }

    if (!function_exists('Response')) {
        function Response()
        {
            return call_user_func_array(\PHPKit\PHPKit::get(strtolower(__FUNCTION__)), func_get_args());
        }
    }

    /**
     * 跳转
     * 
     * @param  string  $action 
     * @param  array|string  $params
     * @return string
     */
    if (!function_exists('redirect')) {
        function redirect($url, $params=[]) {
            return Response()->redirect(url($url, $params));
        }
    }

    if (!function_exists('View')) {
        function View()
        {
            return call_user_func_array(PHPKit\PHPKit::get(strtolower(__FUNCTION__)), func_get_args());
        }
    }

    /**
     * 获取/生成 restful uri
     */
    if (!function_exists('restfuluri')) {
        function restfuluri()
        {
            if (!func_num_args()) {
                return '/'.trim(rawurldecode( 
                    isset($_SERVER['PATH_INFO'])&&$_SERVER['PATH_INFO'] ? ($_SERVER['PATH_INFO']?:'/') :
                    ( (false !== $pos = strpos($_SERVER['REQUEST_URI'], '?')) ? substr($_SERVER['REQUEST_URI'], 0, $pos) : $_SERVER['REQUEST_URI'])
                ), '/');
            } else {
                $args = func_get_args();
                $action = $args[0]; $params = isset($args[1])?$args[1]:'';
                return '/' . ltrim($action, '/') . ($params ? '?'.http_build_query($params) : '');
            }
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
        function eachdir($dir, $callback)
        {
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
        }
    }

    // 注册要用到的工具
    PHPKit\PHPKit::registerTools([
            
        'Heresy' => function () {
            $heresy = PHPKit\Heresy::getInstance();
            $heresy->searchNamespace(['PHPKit\\'])->bewitch('\\');
            return $heresy;
        },

        'Config' => function () {
            $config = Config::getInstance();
            eachdir(APP_DIR .DS .'config', function ($file) use ($config) {
                $config->load($file);
            });
            return $config;
        },
        
        'View' => function () {
            $view = View::getInstance();
            $view->addViewsDir(APP_DIR .DS .'views');//->setCompileDir(Config('view.compiled'));
            return $view;
        },
        
        'DB' => function () {
            $dbconf = Config('database');
            $dbh = new PDO($dbconf['dsn'], $dbconf['username'], $dbconf['password']);
            
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $dbh->setAttribute(PDO::ATTR_AUTOCOMMIT, false);

            return $dbh;
        },

        'HTTP' => function () {
            return new HTTP();
        },

        'Route', 'Response',

    // 设置工具别名alias 及自动载入工具loadTools
    ])->loadTools(['Heresy']);//->registerDirs(['App\\'=>__DIR__]); //->alias('db', 'database')

    Route::setDispatcher(function ($routeInfo){
            
            $callable = false;

            if ($routeInfo[0] == Route::FOUND) {
                if (is_callable($routeInfo[1][1])) {
                    $callable = $routeInfo[1][1];
                } elseif (is_string($routeInfo[1][1]) && strpos($routeInfo[1][1], 'Controller::')) {
                    $callable = 'App\Controller\\'.$routeInfo[1][1];
                } elseif (is_string($routeInfo[1][1]) && (substr($routeInfo[1][1], -4)=='View')) {
                    $callable = function () use ($routeInfo) {
                        return View::render(substr($routeInfo[1][1], 0, -4));
                    };
                }
            } elseif ($routeInfo[0] == Route::NOT_FOUND) {
                // 未找到路由, 并开启了默认路由
                if (Config('app.mvc-defaut-route')) {
                    $arr = explode(
                        '/',
                        trim(path(), '/')
                    );
                    $action = array_pop($arr);
                    $callable = ['App\Controller\\'.implode('\\', $arr).'Controller', $action];
                }
            }

            if (is_callable($callable)) {

                $middlewares = Config('middleware');
                
                $routeInfo[1][0][] = $callable;

                foreach ($routeInfo[1][0] as $middleware) {
                    if (is_callable($middleware)) {
                        $callables[] = $middleware;
                    } else {
                        if (is_string($middleware) && isset($middlewares[$middleware])) {
                            $callables = is_array($middlewares[$middleware]) ? $middlewares[$middleware] : [$middlewares[$middleware]];
                        } else {
                            throw new Exception('中间件未定义 '.$middleware);
                        }
                    }
                }

                foreach ($callables as $callable) {
                    if (is_callable($callable)) {
                        if (!is_null($response = call_user_func_array($callable, array_values($routeInfo[2])))) {
                            break;
                        }
                    } elseif (class_exists($callable)) {
                        if (!is_null($response = call_user_func_array(new $callable, array_values($routeInfo[2])))) {
                            break;
                        }
                    } else {
                        throw new Exception('中间件不能运行 或 最终回调返回了NULL(或未返回任何内容) '.$callable);
                    }
                }

                if (!is_object($response) || get_class($response)!=='PHPKit\Response') {
                    echo Response()->content($response);
                } else {
                    echo Response();
                }
            } else {
                echo Response()->status(404)->message('Not Found')->content(View('errors.404'));
            }
    });

    function Api($method, $uri=false) {
        
        if ($uri==false) {
            $uri = $method;
            $method = 'GET';
        }

        $uri = '/api/'.ltrim($uri, '/');
        
        return Route::dispatch($uri, $method, function ($routeInfo) {
            if ($routeInfo[0] == Route::FOUND) {
                if (is_callable($routeInfo[1][1])) {
                    return call_user_func_array($routeInfo[1][1], $routeInfo[2]);
                }
            }
        });
    }
}