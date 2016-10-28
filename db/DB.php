<?php

namespace PHPKit;

use Exception;

class DB
{
    use LazySingletonTrait, LazyLinkTrait;
    
    protected static $conname;
    protected static $turn = 'auto'; // auto自动 read/write
    
    protected static $config = [];
    protected static $dbContainer=[];

    protected static function _setConfig($config)
    {
        static::$conname = $config['default'];
        static::$config = $config;
    }

    protected static function _getHandle($conname=null, $turn=false)
    {
        $conname = $conname?:static::$conname;
        
        $turn = (($turn?:static::$turn)=='write')?'write':'read'; // auto默认为read
        
        // 首先查看是否已经有实例
        if (!isset(static::$dbContainer[$conname][$turn])) {
            
            // 没有实例查看是否有配置
            if (isset(static::$config['connections'][$conname]) && is_array(static::$config['connections'][$conname])) {
                
                // 对配置校验 #todo
                $config = static::$config['connections'][$conname];

                switch ($config['driver']) {
                    case 'mysql':
                        if (isset($config['host'])) {
                            static::$dbContainer[$conname]['read'] = static::$dbContainer[$conname]['write'] = new \PHPKit\DB\Adapter\MySQL($config);
                        } elseif (isset($config['read']) && isset($config['write'])) {
                            $config['host'] = $config[$turn]['host'];
                            static::$dbContainer[$conname][$turn] = static::$dbContainer[$conname]['write'] = new \PHPKit\DB\Adapter\MySQL($config);
                        } else {
                            throw new Exception('数据库连接配置错误');
                        }
                        break;
                    default:
                        throw new Exception('数据库类型不支持');
                }
            }
        }
        return static::$dbContainer[$conname][$turn];
    }

    protected static function _connection()
    {
		$num = func_num_args();

        if ($num == 0) {
            
            static::getHandle();

        } elseif ($num == 1) {
            $arg = func_get_arg(0);
            if (is_string($arg)) {
                static::turn($arg);
            } elseif (is_array($arg)) {
                static::setConfig($config);
            }
        }
    }
    
    protected static function _turn()
    {
        $num = func_num_args();
        
        if ($num == 1) {
            $tmp = explode('.', func_get_arg(0));
            if (count($tmp)==1) {
                if (in_array($tmp[0], ['auto', 'read', 'write'])) {
                    static::$turn = $tmp[0];
                } elseif (isset(static::$config['connections'][$tmp[0]])) {
                    static::$conname = $tmp[0];
                } else {
                    throw new Exception('turn');
                }
            } elseif (count($tmp)==2) {
                static::turn($tmp[0], $tmp[1]);
            } else {
                throw new Exception('turn');
            }
        } elseif ($num == 2) {
            $conname = func_get_arg(0);
            $turn = func_get_arg(1);

            if (isset(static::$config['connections'][$conname]) && in_array($turn, ['auto', 'read', 'write'])) {
                static::$conname = $conname;
                static::$turn = $turn;
            } else {
                throw new Exception('turn');
            }
        } else {
            throw new Exception('turn');
        }
    }

    protected static function _switchTo()
    {
        $instance = static::getInstance();
        call_user_func_array([$instance, 'turn'], func_get_args());
    }

    public static function getTurn()
    {
        $instance = static::getInstance();
        return static::$turn;
    }
    
    // 用于查询操作
    public static function query($sql, $params=[], $useReadPdo=null)
    {
        $instance = static::getInstance();
        
        $turn = is_null($useReadPdo) ?  ((static::$turn=='auto') ? 'read' : static::$turn) : (($useReadPdo==true) ? 'read' : 'write');
        $db = static::getHandle(static::$conname, $turn);
        $pdo = $db->getPDO();

        $stm = $pdo->prepare($sql);        
        if ($stm->execute($params)) {
            return $stm->fetchAll();
        }

        return false;
    }
    
    // 用于增删改操作
    public static function execute($sql, $params=[], $useReadPdo=null)
    {
        $instance = static::getInstance();

        $turn = is_null($useReadPdo) ?  ((static::$turn=='auto') ? 'write' : static::$turn) : (($useReadPdo==true) ? 'read' : 'write');
        $db = static::getHandle(static::$conname, $turn);
        $pdo = $db->getPDO();

        $stm = $pdo->prepare($sql);
        if ($stm->execute($params)) {
            return $pdo->lastInsertId()?:true;
            //return $pdo->lastInsertId()?:$stm->rowCount();
        }

        return false;
    }





    public static function select($sql, $params=[])
    {
        $instance = static::getInstance();
        return static::query($sql, $params);
    }

    public static function insert($sql, $params=[])
    {
        $instance = static::getInstance();
        
        $turn = static::getTurn();
        static::turn('write');
        $return = static::execute($sql, $params);
        if ($return===false) {
            static::turn($turn);
        }
        return $return;
    }

    public static function delete($sql, $params=[])
    {
        return static::insert($sql, $params);
    }

    public static function update($sql, $params=[])
    {
        return static::insert($sql, $params);
    }












    public static function transaction()
    {
        
    }

    public static function beginTransaction()
    {
        
    }

    public static function rollBack()
    {
    
    }

    public static function commit()
    {
    
    }

    public function __invoke()
    {
        $instance = static::getInstance();
        
        return $instance;    
    }
}
