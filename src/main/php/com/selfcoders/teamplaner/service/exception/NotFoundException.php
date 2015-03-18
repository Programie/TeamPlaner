<?php
namespace com\selfcoders\teamplaner\service\exception;

class NotFoundException extends ServiceException
{
	public function __construct()
	{
		parent::__construct("The requested resource could not be found.");
	}
}