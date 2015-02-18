<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../includes/Config.php";
require_once __DIR__ . "/../includes/DBConnection.php";
require_once __DIR__ . "/../includes/ExtensionClassFactory.php";
require_once __DIR__ . "/../includes/TeamHelper.php";
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

		$teams = $userAuthInstance->getTeams();

		$availableTeams = TeamHelper::getTeams($pdo, $teams);

		if (isset($_GET["team"]))
		{
			$team = $_GET["team"];
		}
		else
		{
			$team = $availableTeams[0]->name;
		}

		$teamId = TeamHelper::getTeamIdIfAllowed($pdo, $team, $teams);
		if ($teamId === null)
		{
			header("HTTP/1.1 403 Forbidden");
			echo "You are not allowed to access this team!";
			exit;
		}

		$query = $pdo->prepare("
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

		$query = $pdo->prepare("
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
			"holidays" => $holidays,
			"teams" => $availableTeams,
			"currentTeam" => $team
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

		$teamId = TeamHelper::getTeamIdIfAllowed($pdo, $_GET["team"], $userAuthInstance->getTeams());
		if ($teamId === null)
		{
			header("HTTP/1.1 403 Forbidden");
			echo "You are not allowed to access this team!";
			exit;
		}

		/**
		 * @var iReport $reportInstance
		 */
		$reportInstance = ExtensionClassFactory::getInstance($config->getValue("reportClass"));

		$reportInstance->setConfig($config);
		$reportInstance->setPDO($pdo);

		$reportInstance->setOutput("php://output");
		$reportInstance->setYear($year);
		$reportInstance->setMonth($month);
		$reportInstance->setTeamId($teamId);

		$reportInstance->configure();

		header("Content-type: " . $reportInstance->getOutputContentType());
		header("Content-Disposition: attachment; filename=" . $reportInstance->getOutputFilename());

		$reportInstance->create();
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

		$teamId = TeamHelper::getTeamIdIfAllowed($pdo, $_GET["team"], $userAuthInstance->getTeams());
		if ($teamId === null)
		{
			header("HTTP/1.1 403 Forbidden");
			echo "You are not allowed to access this team!";
			exit;
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
			$query = $pdo->prepare("
				SELECT `date`, `type`, `userId`
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

		$teamId = TeamHelper::getTeamIdIfAllowed($pdo, $_GET["team"], $userAuthInstance->getTeams());
		if ($teamId === null)
		{
			header("HTTP/1.1 403 Forbidden");
			echo "You are not allowed to access this team!";
			exit;
		}

		$teamMemberQuery = $pdo->prepare("
			SELECT `id`
			FROM `teammembers`
			WHERE `teamId` = :teamId AND `id` = :id
		");

		$deleteEntryQuery = $pdo->prepare("
			DELETE FROM `entries`
			WHERE `id` = :id
		");

		$updateEntryQuery = $pdo->prepare("
			UPDATE `entries`
			SET
				`date` = :date,
				`type` = :type,
				`memberId` = :memberId
			WHERE `id` = :id
		");

		$insertEntryQuery = $pdo->prepare("
			INSERT INTO `entries`
			SET
				`date` = :date,
				`type` = :type,
				`memberId` = :memberId
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
		 * - memberId: The ID of the team member for which the entry should be set
		 */
		foreach ($entries as $entry)
		{
			$teamMemberQuery->execute(array
			(
				":teamId" => $teamId,
				":id" => $entry->memberId
			));

			if (!$teamMemberQuery->rowCount())
			{
				header("HTTP/1.1 403 Forbidden");
				echo "You are not allowed to modify this team!";
				exit;
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
		exit;
	default:
		header("HTTP/1.1 404 Not Found");
		echo "The requested resource was not found!";
		exit;
}