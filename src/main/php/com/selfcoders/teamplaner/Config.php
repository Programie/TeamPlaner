<?php
namespace com\selfcoders\teamplaner;

class Config extends \com\selfcoders\jsonconfig\Config
{
	public function __construct()
	{
		$path = APP_ROOT . "/config";

		parent::__construct($path . "/config.json", $path . "/config.template.json");
	}
}