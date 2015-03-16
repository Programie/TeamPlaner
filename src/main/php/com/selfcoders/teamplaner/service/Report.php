<?php
namespace com\selfcoders\teamplaner\service;

use com\selfcoders\teamplaner\ExtensionClassFactory;
use com\selfcoders\teamplaner\report\iReport;
use com\selfcoders\teamplaner\utils\Date;
use com\selfcoders\teamplaner\utils\TeamHelper;
use DateTime;

class Report extends AbstractService
{
	public function getReport($year, $month, $team)
	{
		if (!$year)
		{
			$year = date("Y");
		}

		if (!$month)
		{
			$month = null;
		}

		if (!$this->config->isValueSet("reportClass"))
		{
			header("HTTP/1.1 500 Internal Server Error");
			echo "Report class not defined!";
			exit;
		}

		$teamId = TeamHelper::getTeamIdIfAllowed($this->pdo, $team, $this->userAuth->getTeams());
		if ($teamId === null)
		{
			header("HTTP/1.1 403 Forbidden");
			echo "You are not allowed to access this team!";
			exit;
		}

		/**
		 * @var iReport $reportInstance
		 */
		$reportInstance = ExtensionClassFactory::getInstance($this->config->getValue("reportClass"));

		$reportInstance->setConfig($this->config);
		$reportInstance->setPDO($this->pdo);

		$reportInstance->setOutput("php://output");
		$reportInstance->setYear($year);
		$reportInstance->setMonth($month);
		$reportInstance->setTeamId($teamId);

		$reportInstance->configure();

		header("Content-type: " . $reportInstance->getOutputContentType());
		header("Content-Disposition: attachment; filename=" . $reportInstance->getOutputFilename());

		$reportInstance->create();
	}

	public function getReportData($year, $month, $team)
	{
		$teamId = TeamHelper::getTeamIdIfAllowed($this->pdo, $team, $this->userAuth->getTeams());
		if ($teamId === null)
		{
			header("HTTP/1.1 403 Forbidden");
			echo "You are not allowed to access this team!";
			exit;
		}

		$types = array();

		foreach ($this->config->getValue("types") as $type)
		{
			if (!isset($type->showInReport) or !$type->showInReport)
			{
				continue;
			}

			$types[$type->name] = $type->title;
		}

		$teamMembers = array();

		$query = $this->pdo->prepare("
			SELECT `teammembers`.`id`, `username`, `additionalInfo`, `startDate`, `endDate`
			FROM `teammembers`
			LEFT JOIN `users` ON `users`.`id` = `teammembers`.`userId`
			WHERE `teamId` = :teamId
		");

		$query->execute(array
		(
			":teamId" => $teamId
		));

		if ($month)
		{
			$rangeStart = new DateTime($year . "-" . $month . "-01");
			$rangeEnd = clone $rangeStart;
			$rangeEnd->modify("last day of this month");
		}
		else
		{
			$rangeStart = new DateTime($year . "-01-01");
			$rangeEnd = new DateTime($year . "-12-31");
		}

		while ($row = $query->fetch())
		{
			if (!Date::isRangeInRange($row->startDate ? new DateTime($row->startDate) : null, $row->endDate ? new DateTime($row->endDate) : null, $rangeStart, $rangeEnd))
			{
				continue;
			}

			$teamMembers[$row->id] = $row;
		}

		$data = array();

		if ($month)
		{
			$query = $this->pdo->prepare("
				SELECT `date`, `type`, `memberId`
				FROM `entries`
				LEFT JOIN `teammembers` ON `teammembers`.`id` = `entries`.`memberId`
				WHERE YEAR(`date`) = :year AND MONTH(`date`) = :month AND `teamId` = :teamId
			");

			$query->execute(array
			(
				":year" => $year,
				":month" => $month,
				":teamId" => $teamId
			));
		}
		else
		{
			$query = $this->pdo->prepare("
				SELECT `date`, `type`, `memberId`
				FROM `entries`
				LEFT JOIN `teammembers` ON `teammembers`.`id` = `entries`.`memberId`
				WHERE YEAR(`date`) = :year AND `teamId` = :teamId
			");

			$query->execute(array
			(
				":year" => $year,
				":teamId" => $teamId
			));
		}

		while ($row = $query->fetch())
		{
			if (!isset($types[$row->type]))
			{
				continue;
			}

			if (!isset($teamMembers[$row->memberId]))
			{
				continue;
			}

			$data[$row->memberId][$row->date] = $row->type;
		}

		$sortedData = array();

		foreach ($data as $memberId => $dates)
		{
			$sortedUserData = array();

			foreach ($dates as $date => $type)
			{
				$sortedUserData[] = array
				(
					"date" => $date,
					"type" => $type
				);
			}

			usort($sortedUserData, function($item1, $item2)
			{
				$time1 = strtotime($item1["date"]);
				$time2 = strtotime($item2["date"]);

				if ($time1 < $time2)
				{
					return -1;
				}

				if ($time1 > $time2)
				{
					return 1;
				}

				return 0;
			});

			$sortedData[] = array
			(
				"username" => $teamMembers[$memberId]->username,
				"additionalUserInfo" => $teamMembers[$memberId]->additionalInfo,
				"entries" => $sortedUserData
			);
		}

		usort($sortedData, function($item1, $item2)
		{
			if ($item1["username"] < $item2["username"])
			{
				return -1;
			}

			if ($item1["username"] > $item2["username"])
			{
				return 1;
			}

			return 0;
		});

		header("Content-Type: application/json");

		echo json_encode(array
		(
			"month" => $month,
			"year" => $year,
			"data" => $sortedData,
			"types" => $types
		));
	}
}