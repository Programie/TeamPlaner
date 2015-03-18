<?php
namespace com\selfcoders\teamplaner\service;

use com\selfcoders\teamplaner\ExtensionClassFactory;
use com\selfcoders\teamplaner\Holiday;
use com\selfcoders\teamplaner\service\exception\ForbiddenException;
use com\selfcoders\teamplaner\type\TypeCollection;
use com\selfcoders\teamplaner\utils\TeamHelper;

class Entries extends AbstractService
{
	public function getAll()
	{
		$year = $this->parameters->year;
		if (!$year)
		{
			$year = date("Y");
		}

		$teams = $this->userAuth->getTeams();

		$availableTeams = TeamHelper::getTeams($this->pdo, $teams);

		$team = $this->parameters->team;
		if (!$team)
		{
			$team = $availableTeams[0]->name;
		}

		$teamId = TeamHelper::getTeamIdIfAllowed($this->pdo, $team, $teams);
		if ($teamId === null)
		{
			throw new ForbiddenException;
		}

		$query = $this->pdo->prepare("
			SELECT `entries`.`id`, `date`, `type`, `memberId`, `userId`
			FROM `entries`
			LEFT JOIN `teammembers` ON `teammembers`.`id` = `entries`.`memberId`
			WHERE YEAR(`date`) = :year AND `teamId` = :teamId
		");

		$query->execute(array
		(
			":year" => $year,
			":teamId" => $teamId
		));

		$entries = array();

		while ($row = $query->fetch())
		{
			if (!isset($entries[$row->date]))
			{
				$entries[$row->date] = array();
			}

			$entries[$row->date][] = array
			(
				"id" => (int) $row->id,
				"type" => $row->type,
				"memberId" => (int) $row->memberId,
				"userId" => (int) $row->userId
			);
		}

		$holidays = array();

		if ($this->config->isValueSet("holidaysMethod"))
		{
			list($className, $methodName) = explode("#", $this->config->getValue("holidaysMethod"));

			$holidaysInstance = ExtensionClassFactory::getInstance($className);

			if (method_exists($holidaysInstance, $methodName))
			{
				$holidays = $holidaysInstance->$methodName($year);
			}
		}

		$cleanedHolidays = array();

		/**
		 * @var $holiday Holiday
		 */
		foreach ($holidays as $holiday)
		{
			$cleanedHolidays[$holiday->date->format("Y-m-d")] = $holiday->title;
		}

		// TODO: Do not call another service
		$userInstance = new User($this->config, $this->userAuth);
		$userInstance->parameters = $this->parameters;
		$userInstance->data = $this->data;

		return array
		(
			"year" => $year,
			"username" => $this->userAuth->getUsername(),
			"entries" => $entries,
			"users" => $userInstance->getUsersOfTeam(),
			"types" => $this->getTypes(),
			"colors" => array
			(
				"holiday" => $this->config->getValue("colors.holiday"),
				"today" => $this->config->getValue("colors.today"),
				"weekend" => $this->config->getValue("colors.weekend")
			),
			"holidays" => $cleanedHolidays,
			"teams" => $availableTeams,
			"currentTeam" => $team
		);
	}

	public function getTypes()
	{
		$collection = new TypeCollection($this->config->getValue("types"));

		return $collection->getTypes();
	}

	public function editMultiple()
	{
		$teamId = TeamHelper::getTeamIdIfAllowed($this->pdo, $this->parameters->team, $this->userAuth->getTeams());
		if ($teamId === null)
		{
			throw new ForbiddenException;
		}

		$teamMemberQuery = $this->pdo->prepare("
			SELECT `id`
			FROM `teammembers`
			WHERE `teamId` = :teamId AND `id` = :id
		");

		$deleteEntryQuery = $this->pdo->prepare("
			DELETE FROM `entries`
			WHERE `id` = :id
		");

		$updateEntryQuery = $this->pdo->prepare("
			UPDATE `entries`
			SET
				`date` = :date,
				`type` = :type,
				`memberId` = :memberId
			WHERE `id` = :id
		");

		$insertEntryQuery = $this->pdo->prepare("
			INSERT INTO `entries`
			SET
				`date` = :date,
				`type` = :type,
				`memberId` = :memberId
		");

		$types = $this->getTypes();

		/**
		 * Each entry contains the following properties:
		 * - id: The ID of the entry (if already existing and should be updated)
		 * - date: The date in format "YYYY-MM-DD" for the entry
		 * - type: The new type for the entry
		 * - memberId: The ID of the team member for which the entry should be set
		 */
		foreach ($this->data as $entry)
		{
			$teamMemberQuery->execute(array
			(
				":teamId" => $teamId,
				":id" => $entry->memberId
			));

			if (!$teamMemberQuery->rowCount())
			{
				throw new ForbiddenException;
			}

			// TODO: $entry might be an entry of another team the user does not have access to!

			// Type does not exist or should not be saved
			if (!isset($types[$entry->type]) or $types[$entry->type]->noSave)
			{
				if ($entry->id)
				{
					// Delete existing entry
					$deleteEntryQuery->execute(array
					(
						":id" => $entry->id
					));
				}
				continue;
			}

			// Existing entry
			if ($entry->id)
			{
				// Update existing entry
				$updateEntryQuery->execute(array
				(
					":id" => $entry->id,
					":date" => $entry->date,
					":type" => $entry->type,
					":memberId" => $entry->memberId
				));
				continue;
			}

			// Create new entry
			$insertEntryQuery->execute(array
			(
				":date" => $entry->date,
				":type" => $entry->type,
				":memberId" => $entry->memberId
			));
		}

		return null;
	}
}