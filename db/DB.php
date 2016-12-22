<?php

namespace PHPKit;

use Exception;

class DB
{
    use LazySingletonTrait, LazyLinkTrait;
    
    protected static $conname;
    protected static $switch = 'auto'; // auto自动 read/write
    
    protected static $config = [];
    protected static $dbContainer=[];

    protected static function API_setConfig($config)
    {
        static::$conname = $config['default'];
        static::$config = $config;
    }

    protected static function API_getHandle($conname=null, $switch=false)
    {
        $conname = $conname?:static::$conname;
        
        $switch = (($switch?:static::$switch)=='write')?'write':'read'; // auto默认为read
        
        // 首先查看是否已经有实例
        if (!isset(static::$dbContainer[$conname][$switch])) {
            
            // 没有实例查看是否有配置
            if (isset(static::$config['connections'][$conname]) && is_array(static::$config['connections'][$conname])) {
                
                // 对配置校验 #todo
                $config = static::$config['connections'][$conname];

                switch ($config['driver']) {
                    case 'mysql':
                        if (isset($config['host'])) {
                            static::$dbContainer[$conname]['read'] = static::$dbContainer[$conname]['write'] = new \PHPKit\DB\Adapter\MySQL($config);
                        } elseif (isset($config['read']) && isset($config['write'])) {
                            $config['host'] = $config[$switch]['host'];
                            static::$dbContainer[$conname][$switch] = static::$dbContainer[$conname]['write'] = new \PHPKit\DB\Adapter\MySQL($config);
                        } else {
                            throw new Exception('数据库连接配置错误');
                        }
                        break;
                    default:
                        throw new Exception('数据库类型不支持');
                }
            }
        }
        return static::$dbContainer[$conname][$switch];
    }

    protected static function API_connection()
    {
		$num = func_num_args();

        if ($num == 0) {
            
            static::getHandle();

        } elseif ($num == 1) {
            $arg = func_get_arg(0);
            if (is_string($arg)) {
                static::switchTo($arg);
            } elseif (is_array($arg)) {
                static::setConfig($config);
            }
        }
    }

    protected static function API_switchTo()
    {
        $num = func_num_args();
        
        if ($num == 1) {
            $tmp = explode('.', func_get_arg(0));
            if (count($tmp)==1) {
                if (in_array($tmp[0], ['auto', 'read', 'write'])) {
                    static::$switch = $tmp[0];
                } elseif (isset(static::$config['connections'][$tmp[0]])) {
                    static::$conname = $tmp[0];
                } else {
                    throw new Exception('turn');
                }
            } elseif (count($tmp)==2) {
                static::switchTo($tmp[0], $tmp[1]);
            } else {
                throw new Exception('turn');
            }
        } elseif ($num == 2) {
            $conname = func_get_arg(0);
            $switch = func_get_arg(1);

            if (isset(static::$config['connections'][$conname]) && in_array($switch, ['auto', 'read', 'write'])) {
                static::$conname = $conname;
                static::$switch = $switch;
            } else {
                throw new Exception('turn');
            }
        } else {
            throw new Exception('turn');
        }
    }

    protected static function API_getSwitch()
    {
        return static::$switch;
    }
    
    // 用于查询操作
    protected static function API_query($sql, $params=[], $useReadPdo=null)
    {
        $instance = static::getInstance();
        
        $switch = is_null($useReadPdo) ?  ((static::$switch=='auto') ? 'read' : static::$switch) : (($useReadPdo==true) ? 'read' : 'write');
        $db = static::getHandle(static::$conname, $switch);
        $pdo = $db->getPDO();

        $stm = $pdo->prepare($sql);        
        if ($stm->execute($params)) {
            return $stm->fetchAll();
        }

        return false;
    }
    
    // 用于增删改操作
    protected static function API_execute($sql, $params=[], $useReadPdo=null)
    {
        $instance = static::getInstance();

        $switch = is_null($useReadPdo) ?  ((static::$switch=='auto') ? 'write' : static::$switch) : (($useReadPdo==true) ? 'read' : 'write');
        $db = static::getHandle(static::$conname, $switch);
        $pdo = $db->getPDO();

        $stm = $pdo->prepare($sql);
        if ($stm->execute($params)) {
            return $pdo->lastInsertId()?:true;
            //return $pdo->lastInsertId()?:$stm->rowCount();
        }

        return false;
    }





    protected static function API_select($sql, $params=[])
    {
        return static::query($sql, $params);
    }

    protected static function API_insert($sql, $params=[])
    {        
        $switch = static::getSwitch();
        static::switchTo('write');
        $return = static::execute($sql, $params);
        if ($return===false) {
            static::switchTo($switch);
        }
        return $return;
    }

    protected static function API_delete($sql, $params=[])
    {
        static::switchTo('write');
        return static::insert($sql, $params);
    }

    protected static function API_update($sql, $params=[])
    {
        static::switchTo('write');
        return static::insert($sql, $params);
    }












    protected static function API_transaction()
    {
        
    }

    protected static function API_beginTransaction()
    {
        
    }

    protected static function API_rollBack()
    {
    
    }

    protected static function API_commit()
    {
    
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        
        return $instance;    
    }
}
