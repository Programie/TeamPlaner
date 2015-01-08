<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../includes/Config.php";
require_once __DIR__ . "/../includes/DBConnection.php";
require_once __DIR__ . "/../includes/UserAuthFactory.php";

$config = new Config();

$userAuthInstance = UserAuthFactory::getProvider($config->getValue("userAuth"));
if (!$userAuthInstance)
{
	header("HTTP/1.1 500 Internal Server Error");
	echo "Unable to load User Auth provider!";
	exit;
}

if (!$userAuthInstance->checkAuth())
{
	header("HTTP/1.1 401 Unauthorized");
	echo "You have to authenticate first!";
	exit;
}

if (!$userAuthInstance->checkPermissions())
{
	header("HTTP/1.1 403 Forbidden");
	echo "You are not allowed to access this service!";
	exit;
}

$pdo = DBConnection::getConnection($config);

switch ($_GET["type"])
{
	case "getData":
		if (isset($_GET["year"]))
		{
			$year = $_GET["year"];
		}
		else
		{
			$year = date("Y");
		}

		$query = $pdo->prepare("
			SELECT `id`, `date`, `type`, `userId`
			FROM `entries`
			WHERE YEAR(`date`) = :year
		");

		$query->execute(array
		(
			":year" => $year
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
				"userId" => (int) $row->userId
			);
		}

		$query = $pdo->query("SELECT `id`, `username` FROM `users` ORDER BY `username` ASC");

		$users = array();

		while ($row = $query->fetch())
		{
			$row->id = (int) $row->id;

			$users[] = $row;
		}

		$holidays = array();

		if (file_exists(__DIR__ . "/../includes/getHolidays.php"))
		{
			require_once __DIR__ . "/../includes/getHolidays.php";

			if (function_exists("getHolidays"))
			{
				$holidays = getHolidays($year);
			}
		}

		header("Content-Type: application/json");

		echo json_encode(array
		(
			"year" => $year,
			"username" => $userAuthInstance->getUsername(),
			"entries" => $entries,
			"users" => $users,
			"types" => $config->getValue("types"),
			"colors" => array
			(
				"weekend" => $config->getValue("colors.weekend"),
				"holiday" => $config->getValue("colors.holiday")
			),
			"holidays" => $holidays
		));
		exit;
	case "getReportData":
		if (isset($_GET["year"]))
		{
			$year = $_GET["year"];
		}
		else
		{
			$year = date("Y");
		}

		if (isset($_GET["month"]))
		{
			$month = $_GET["month"];
		}
		else
		{
			$month = date("m");
		}

		$types = array();

		foreach ($config->getValue("types") as $type)
		{
			if (!isset($type->showInReport) or !$type->showInReport)
			{
				continue;
			}

			$types[$type->name] = $type->title;
		}

		$data = array();

		$query = $pdo->prepare("
			SELECT `date`, `type`, `username`
			FROM `entries`
			LEFT JOIN `users` ON `users`.`id` = `userId`
			WHERE YEAR(`date`) = :year AND MONTH(`date`) = :month
			ORDER BY `date` ASC
		");

		$query->execute(array
		(
			":year" => $year,
			":month" => $month
		));

		while ($row = $query->fetch())
		{
			if (!isset($types[$row->type]))
			{
				continue;
			}

			$data[$row->username][$row->date] = $row->type;
		}

		header("Content-Type: application/json");

		echo json_encode(array
		(
			"month" => $month,
			"year" => $year,
			"data" => $data,
			"types" => $types
		));
		exit;
	case "setData":
		if ($_SERVER["REQUEST_METHOD"] != "POST")
		{
			header("HTTP/1.1 405 Method Not Allowed");
			echo "Data must be sent using POST method!";
			exit;
		}

		$entries = json_decode(file_get_contents("php://input"));
		if (!$entries)
		{
			header("HTTP/1.1 400 Bad Request");
			echo "Unable to decode posted JSON data!";
			exit;
		}

		$deleteEntryQuery = $pdo->prepare("
			DELETE FROM `entries`
			WHERE `id` = :id
		");

		$updateEntryQuery = $pdo->prepare("
			UPDATE `entries`
			SET
				`date` = :date,
				`type` = :type,
				`userId` = :userId
			WHERE `id` = :id
		");

		$insertEntryQuery = $pdo->prepare("
			INSERT INTO `entries`
			SET
				`date` = :date,
				`type` = :type,
				`userId` = :userId
		");

		$types = array();

		foreach ($config->getValue("types") as $type)
		{
			$types[$type->name] = $type;
		}

		/**
		 * Each entry contains the following properties:
		 * - id: The ID of the entry (if already existing and should be updated)
		 * - date: The date in format "YYYY-MM-DD" for the entry
		 * - type: The new type for the entry
		 * - userId: The ID of the user for which the entry should be set
		 */
		foreach ($entries as $entry)
		{
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
					":userId" => $entry->userId
				));
				continue;
			}

			// Create new entry
			$insertEntryQuery->execute(array
			(
				":date" => $entry->date,
				":type" => $entry->type,
				":userId" => $entry->userId
			));
		}
		exit;
	default:
		header("HTTP/1.1 404 Not Found");
		echo "The requested resource was not found!";
		exit;
}