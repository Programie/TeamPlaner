<?php
namespace com\selfcoders\teamplaner\service\exception;

class ForbiddenException extends ServiceException
{
	public function __construct()
	{
		parent::__construct("You are not allowed to access this resource.");
	}
}