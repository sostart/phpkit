<?php

namespace PHPKit\Cache\Adapter;

use Memcache;

class MemcacheCache implements \PHPKit\CacheInterface
{
    protected $handle;

    public function __construct($config)
    {
        $this->handle = new Memcache();
        foreach ($config['servers'] as $row) {
            $this->handle->addServer($row[0], $row[1], true, $row[2]);
        }
    }

    public function set($key, $value, $expire=0)
    {
        return $this->handle->set($key, $value, 0, $expire);
    }

    public function add($key, $value, $expire=0)
    {
        return $this->handle->add($key, $value, 0, $expire);
    }
    
    public function get($key, $default=null)
    {
        $return = $this->handle->get($key);
        if ($return === false) {
            return $default;
        }
        return $return;
    }
    
    public function delete($key)
    {
        return $this->handle->delete($key);
    }

    public function flush()
    {
        return $this->handle->flush();
    }

    public function increment($key, $offset=1)
    {
        return $this->handle->increment($key, $offset);
    }

    public function decrement($key, $offset=1)
    {
        return $this->handle->decrement($key, $offset);
    }
}
