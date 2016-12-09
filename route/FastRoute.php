<?php

namespace PHPKit\Route;

use Exception;
use PHPKit\LazySingletonTrait;
use PHPKit\LazyLinkTrait;

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

        return [FastRoute::NOT_FOUND, '', []];
    }
}
