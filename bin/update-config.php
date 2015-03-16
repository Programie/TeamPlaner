<?php
use com\selfcoders\teamplaner\Config;

require_once __DIR__ . "/../bootstrap.php";

$config = new Config();

$config->save(null, JSON_PRETTY_PRINT, true);