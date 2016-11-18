<?php
namespace com\selfcoders\teamplaner\router;

use AltoRouter;

class Router extends AltoRouter
{
    public function map($method, $route, Target $target)
    {
        if (is_array($method)) {
            $method = implode("|", $method);
        }

        parent::map($method, $route, $target);
    }
}