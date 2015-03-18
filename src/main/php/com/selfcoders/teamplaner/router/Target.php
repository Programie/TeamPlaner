<?php
namespace com\selfcoders\teamplaner\router;

class Target
{
	public $class;
	public $method;

	public function __construct($class, $method)
	{
		$this->class = $class;
		$this->method = $method;
	}
}