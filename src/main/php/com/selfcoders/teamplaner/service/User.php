<?php
namespace com\selfcoders\teamplaner\service;

use com\selfcoders\teamplaner\service\exception\ForbiddenException;
use com\selfcoders\teamplaner\service\exception\NotFoundException;
use com\selfcoders\teamplaner\utils\TeamHelper;

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

	public function getUsersOfTeam()
	{
		$teams = $this->userAuth->getTeams();

		$team = $this->parameters->team;
		if (!$team)
		{
			$availableTeams = TeamHelper::getTeams($this->pdo, $teams);
			$team = $availableTeams[0]->name;
		}

		$teamId = TeamHelper::getTeamIdIfAllowed($this->pdo, $team, $teams);
		if ($teamId === null)
		{
			throw new ForbiddenException;
		}

		$query = $this->pdo->prepare("
			SELECT `teammembers`.`id` AS `memberId`, `userId`, `username`, `additionalInfo`, `startDate`, `endDate`
			FROM `teammembers`
			LEFT JOIN `users` ON `users`.`id` = `teammembers`.`userId`
			WHERE `teamId` = :teamId
			ORDER BY `username` ASC
		");

		$query->execute(array
		(
			":teamId" => $teamId
		));

		$users = array();

		while ($row = $query->fetch())
		{
			$row->memberId = (int) $row->memberId;
			$row->userId = (int) $row->userId;

			$users[] = $row;
		}

		return $users;
	}
}