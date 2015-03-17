<?php
namespace com\selfcoders\teamplaner\service;

use DateTime;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;

class ICalendarData extends AbstractService
{
	public function getData()
	{
		$calendar = new Calendar("TeamPlaner");

		$calendar->setPublishedTTL($this->config->getValue("iCal.ttl"));

		$query = $this->pdo->prepare("
			SELECT `date`, `type`
			FROM `entries`
			LEFT JOIN `teammembers` ON `teammembers`.`id` = `entries`.`memberId`
			LEFT JOIN `users` ON `users`.`id` = `teammembers`.`userId`
			WHERE `username` = :username
		");

		$query->execute(array
		(
			":username" => $this->userAuth->getUsername()
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