<?php
require_once __DIR__ . "/../includes/Config.php";

$config = new Config();

$config->save(null, JSON_PRETTY_PRINT, true);