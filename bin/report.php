<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../includes/DBConnection.php";
require_once __DIR__ . "/../includes/Config.php";
require_once __DIR__ . "/../includes/ExtensionClassFactory.php";

if (count($argv) < 2)
{
	echo "Usage: " . $argv[0] . " <year> [<month> [<output file>]]";
	exit;
}

$year = @$argv[1];
$month = @$argv[2];
$output = @$argv[3];

if (!$month)
{
	$month = null;
}

if (!$output)
{
	$output = "php://stdout";
}

$config = new Config();

if (!$config->isValueSet("reportClass"))
{
	echo "Report class not defined!";
	exit;
}

$pdo = DBConnection::getConnection($config);

/**
 * @var iReport $reportInstance
 */
$reportInstance = ExtensionClassFactory::getInstance($config->getValue("reportClass"));

$reportInstance->setConfig($config);
$reportInstance->setPDO($pdo);

$reportInstance->create($output, $year, $month);