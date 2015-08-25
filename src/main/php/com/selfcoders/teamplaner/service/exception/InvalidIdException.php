<?php
namespace com\selfcoders\teamplaner\service\exception;

class InvalidIdException extends ServiceException
{
	public function __construct()
	{
		parent::__construct("The given ID is invalid!");
	}
}