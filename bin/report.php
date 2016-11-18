<?php
use com\selfcoders\teamplaner\Config;
use com\selfcoders\teamplaner\DBConnection;
use com\selfcoders\teamplaner\ExtensionClassFactory;
use com\selfcoders\teamplaner\report\iReport;

require_once __DIR__ . "/../bootstrap.php";

if (count($argv) < 3) {
    echo "Usage: " . $argv[0] . " <team> <year> [<month> [<output file>]]";
    exit;
}

$team = @$argv[1];
$year = @$argv[2];
$month = @$argv[3];
$output = @$argv[4];

if (!$month) {
    $month = null;
}

if (!$output) {
    $output = "php://stdout";
}

$config = new Config();

if (!$config->isValueSet("reportClass")) {
    echo "Report class not defined!";
    exit;
}

$pdo = DBConnection::getConnection($config);

$query = $pdo->prepare("SELECT `id` FROM `teams` WHERE `name` = :name");

$query->execute(array
(
    ":name" => $team
));

if (!$query->rowCount()) {
    echo "Team not found!";
    exit;
}

$teamId = $query->fetch()->id;

/**
 * @var iReport $reportInstance
 */
$reportInstance = ExtensionClassFactory::getInstance($config->getValue("reportClass"));

$reportInstance->setConfig($config);
$reportInstance->setPDO($pdo);

$reportInstance->setOutput($output);
$reportInstance->setYear($year);
$reportInstance->setMonth($month);
$reportInstance->setTeamId($teamId);

$reportInstance->configure();

$reportInstance->create();