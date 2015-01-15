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

		$query = $pdo->query("SELECT `id`, `username`, `additionalInfo` FROM `users` ORDER BY `username` ASC");

		$users = array();

		while ($row = $query->fetch())
		{
			$row->id = (int) $row->id;

			$users[] = $row;
		}

		$holidays = array();

		if ($config->isValueSet("holidaysMethod"))
		{
			list($className, $methodName) = explode("#", $config->getValue("holidaysMethod"));

			$holidaysInstance = ExtensionClassFactory::getInstance($className);

			if (method_exists($holidaysInstance, $methodName))
			{
				$holidays = $holidaysInstance->$methodName($year);
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
				"holiday" => $config->getValue("colors.holiday"),
				"today" => $config->getValue("colors.today"),
				"weekend" => $config->getValue("colors.weekend")
			),
			"holidays" => $holidays
		));
		exit;
	case "getReport":
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
			$month = null;
		}

		if (!$config->isValueSet("reportClass"))
		{
			header("HTTP/1.1 500 Internal Server Error");
			echo "Report class not defined!";
			exit;
		}

		/**
		 * @var iReport $reportInstance
		 */
		$reportInstance = ExtensionClassFactory::getInstance($config->getValue("reportClass"));

		$reportInstance->setConfig($config);
		$reportInstance->setPDO($pdo);

		$reportInstance->create($year, $month);
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
			$month = null;
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

		$userInfo = array();

		$query = $pdo->query("
			SELECT `id`, `username`, `additionalInfo`
			FROM `users`
		");

		while ($row = $query->fetch())
		{
			$userInfo[$row->id] = $row;
		}

		$data = array();

		if ($month)
		{
			$query = $pdo->prepare("
				SELECT `date`, `type`, `userId`
				FROM `entries`
				WHERE YEAR(`date`) = :year AND MONTH(`date`) = :month
			");

			$query->execute(array
			(
				":year" => $year,
				":month" => $month
			));
		}
		else
		{
			$query = $pdo->prepare("
				SELECT `date`, `type`, `userId`
				FROM `entries`
				WHERE YEAR(`date`) = :year
			");

			$query->execute(array
			(
				":year" => $year
			));
		}

		while ($row = $query->fetch())
		{
			if (!isset($types[$row->type]))
			{
				continue;
			}

			$data[$row->userId][$row->date] = $row->type;
		}

		$sortedData = array();

		foreach ($data as $userId => $dates)
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
				"username" => $userInfo[$userId]->username,
				"additionalUserInfo" => $userInfo[$userId]->additionalInfo,
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