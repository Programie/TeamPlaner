<?php
namespace com\selfcoders\teamplaner\service;

use com\selfcoders\teamplaner\utils\TeamHelper;
use DateTime;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;

class ICalendarData extends AbstractService
{
	public function getData($team, $memberId)
	{
		$teams = $this->userAuth->getTeams();

		$availableTeams = TeamHelper::getTeams($this->pdo, $teams);

		if (!$team)
		{
			$team = $availableTeams[0]->name;
		}

		$teamId = TeamHelper::getTeamIdIfAllowed($this->pdo, $team, $teams);
		if ($teamId === null)
		{
			header("HTTP/1.1 403 Forbidden");
			echo "You are not allowed to access this team!";
			exit;
		}

		$calendar = new Calendar("TeamPlaner");

		$calendar->setPublishedTTL($this->config->getValue("ical.ttl"));

		$query = $this->pdo->prepare("
			SELECT `date`, `type`
			FROM `entries`
			LEFT JOIN `teammembers` ON `teammembers`.`id` = `entries`.`memberId`
			WHERE `teamId` = :teamId AND `memberId` = :memberId
		");

		$query->execute(array
		(
			":teamId" => $teamId,
			":memberId" => $memberId
		));

		$types = array();

		foreach ($this->config->getValue("types") as $type)
		{
			$types[$type->name] = $type->title;
		}

		while ($row = $query->fetch())
		{
			$event = new Event();

			$event->setDtStart(new DateTime($row->date));
			$event->setDtEnd(new DateTime($row->date));
			$event->setNoTime(true);
			$event->setSummary($types[$row->type]);

			$calendar->addComponent($event);
		}

		header("Content-Type: text/calendar; charset=utf-8");
		header("Content-Disposition: attachment; filename='calendar.ics'");
		echo $calendar->render();
	}
}