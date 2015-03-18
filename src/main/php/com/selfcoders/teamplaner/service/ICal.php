<?php
namespace com\selfcoders\teamplaner\service;

use com\selfcoders\teamplaner\type\TypeCollection;
use DateTime;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;

class ICal extends AbstractService
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

		$typeCollection = new TypeCollection($this->config->getValue("types"));

		while ($row = $query->fetch())
		{
			$type = $typeCollection->getTypeByName($row->type);
			if ($type === null)
			{
				continue;
			}

			$event = new Event();

			$event->setDtStart(new DateTime($row->date));
			$event->setDtEnd(new DateTime($row->date));
			$event->setNoTime(true);
			$event->setSummary($type->title);

			$calendar->addComponent($event);
		}

		header("Content-Type: text/calendar; charset=utf-8");
		echo $calendar->render();

		return null;
	}
}