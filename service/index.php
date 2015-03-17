<?php
use com\selfcoders\teamplaner\auth\UserAuthFactory;
use com\selfcoders\teamplaner\Config;
use com\selfcoders\teamplaner\service\ICalendarData;
use com\selfcoders\teamplaner\service\MainData;
use com\selfcoders\teamplaner\service\Report;

require_once __DIR__ . "/../bootstrap.php";

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

// TODO: This should be done in some better way...
switch ($_GET["type"])
{
	case "getData":
		$service = new MainData($config, $userAuthInstance);
		$service->getData(isset($_GET["year"]) ? $_GET["year"] : null, isset($_GET["team"]) ? $_GET["team"] : null);
		exit;
	case "getiCal":
		$service = new ICalendarData($config, $userAuthInstance);
		$service->getData(isset($_GET["team"]) ? $_GET["team"] : null, $_GET["member"]);
		exit;
	case "getReport":
		$service = new Report($config, $userAuthInstance);
		$service->getReport(isset($_GET["year"]) ? $_GET["year"] : null, isset($_GET["month"]) ? $_GET["month"] : null, $_GET["team"]);
		exit;
	case "getReportData":
		$service = new Report($config, $userAuthInstance);
		$service->getReportData(isset($_GET["year"]) ? $_GET["year"] : null, isset($_GET["month"]) ? $_GET["month"] : null, $_GET["team"]);
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

		$service = new MainData($config, $userAuthInstance);
		$service->setData($entries, $_GET["team"]);
		exit;
	default:
		header("HTTP/1.1 404 Not Found");
		echo "The requested resource was not found!";
		exit;
}