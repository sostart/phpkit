<?php

namespace PHPKit;

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
            $file = $dir.DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, $view).'.php';
            $blade = $dir.DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, $view).'.blade.php';
            
            if (file_exists($file)) {
                return static::renderFile($file, $data);
            } elseif (file_exists($blade)) {
                return static::renderBlade($blade, $data);
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
