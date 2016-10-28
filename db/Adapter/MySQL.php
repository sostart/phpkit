<?php

namespace PHPKit\DB\Adapter;

use PDO;

class MySQL
{
    protected $pdo;
    
    protected $prefix = '';

    public function __construct($config)
    {
        $dsn = "mysql:host={$config['host']};".(isset($config['port'])?"port={$config['port']};":'')."dbname={$config['database']}";

        $this->pdo = new PDO($dsn, $config['username'], $config['password']);
        
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, false);

        $this->pdo->prepare("set names '{$config['charset']}'".($config['collation']?" collate '{$config['collation']}'":''))->execute();

        if (isset($config['timezone'])) {
            $this->pdo->prepare(
                'set time_zone="'.$config['timezone'].'"'
            )->execute();
        }

        if (isset($config['prefix'])) {
            $this->prefix = $config['prefix'];
        }
    }

    public function getPDO()
    {
        return $this->pdo;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }
}