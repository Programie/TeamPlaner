<?php
namespace com\selfcoders\teamplaner\router;

use AltoRouter;

class Router extends AltoRouter
{
    public function map($method, $route, $target, $name = null)
    {
        if (is_array($method)) {
            $method = implode("|", $method);
        }

        parent::map($method, $route, $target, $name);
    }
}