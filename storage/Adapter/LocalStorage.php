<?php

namespace PHPKit\Storage\Adapter;

class LocalStorage
{
    private $config = [];

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function get($file)
    {
        return file_get_contents($this->config['root'] . $file);
    }

    public function put($file, $contents)
    {
        return file_put_contents($this->config['root'] . $file, $contents);
    }

    public function isFile($file)
    {
        return is_file($this->config['root'] . $file);
    }

    public function isDir($dir)
    {
        return is_dir($this->config['root'] . $dir);
    }

    public function mkdir($dir, $mode=0777, $recursive=true)
    {
        return mkdir($this->config['root'] . $dir, $mode, $recursive);
    }
}