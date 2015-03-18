<?php
namespace com\selfcoders\teamplaner\service;

use com\selfcoders\teamplaner\service\exception\NotFoundException;

class User extends AbstractService
{
	public function getToken()
	{
		$query = $this->pdo->prepare("
			SELECT `token`
			FROM `users`
			WHERE `username` = :username
		");

		$query->execute(array
		(
			":username" => $this->userAuth->getUsername()
		));

		if (!$query->rowCount())
		{
			throw new NotFoundException;
		}

		$token = $query->fetch()->token;

		if ($token === null)
		{
			$token = md5(uniqid());

			$query = $this->pdo->prepare("
				UPDATE `users`
				SET `token` = :token
				WHERE `username` = :username
			");

			$query->execute(array
			(
				":token" => $token,
				":username" => $this->userAuth->getUsername()
			));
		}

		return array
		(
			"token" => $token
		);
	}
}