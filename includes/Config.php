<?php
require_once __DIR__ . "/../vendor/autoload.php";

class Config extends \com\selfcoders\jsonconfig\Config
{
	public function __construct()
	{
		parent::__construct(__DIR__ . "/../config/config.json", __DIR__ . "/../config/config.template.json");
	}
}