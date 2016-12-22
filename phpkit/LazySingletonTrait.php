<?php

namespace PHPKit;

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
