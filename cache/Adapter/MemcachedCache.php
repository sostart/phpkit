<?php

namespace PHPKit\Cache\Adapter;

use Memcached;

class MemcachedCache implements \PHPKit\CacheInterface
{
    protected $handle;

    public function __construct($config)
    {
        $this->handle = new Memcached($config['persistent_id']);
        $this->handle->addServers($config['servers']);
    }

    public function set($key, $value, $expire=0)
    {
        return $this->handle->set($key, $value, $expire);
    }

    public function add($key, $value, $expire=0)
    {
        return $this->handle->add($key, $value, $expire);
    }
    
    public function get($key, $default=null)
    {
        $return = $this->handle->get($key);
        if ($return === false) {
            if ($this->handle->getResultCode()===Memcached::RES_NOTFOUND) {
                return $default;
            }
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
