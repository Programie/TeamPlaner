<?php
use com\selfcoders\teamplaner\Config;
use com\selfcoders\teamplaner\DBConnection;
use com\selfcoders\teamplaner\ExtensionClassFactory;
use com\selfcoders\teamplaner\report\iReport;

require_once __DIR__ . "/../bootstrap.php";

if (count($argv) < 4)
{
	echo "Usage: " . $argv[0] . " <recipient> <team> <year> [<month>]";
	exit;
}

$recipient = @$argv[1];
$team = @$argv[2];
$year = @$argv[3];
$month = @$argv[4];

if (!$month)
{
	$month = null;
}

$config = new Config();

if (!$config->isValueSet("reportClass"))
{
	echo "Report class not defined!";
	exit;
}

$pdo = DBConnection::getConnection($config);

$query = $pdo->prepare("SELECT `id` FROM `teams` WHERE `name` = :name");

$query->execute(array
(
	":name" => $team
));

if (!$query->rowCount())
{
	echo "Team not found!";
	exit;
}

$teamId = $query->fetch()->id;

$tempFile = tempnam(sys_get_temp_dir(), "calendar-report");

/**
 * @var iReport $reportInstance
 */
$reportInstance = ExtensionClassFactory::getInstance($config->getValue("reportClass"));

$reportInstance->setConfig($config);
$reportInstance->setPDO($pdo);

$reportInstance->setOutput($tempFile);
$reportInstance->setYear($year);
$reportInstance->setMonth($month);
$reportInstance->setTeamId($teamId);

$reportInstance->configure();

$reportInstance->create();

$transport = Swift_SmtpTransport::newInstance();

if ($config->isValueSet("reportMail.smtp.host"))
{
	$transport->setHost($config->getValue("reportMail.smtp.host"));
}

if ($config->isValueSet("reportMail.smtp.port"))
{
	$transport->setPort($config->getValue("reportMail.smtp.port"));
}

if ($config->isValueSet("reportMail.smtp.encryption"))
{
	$transport->setEncryption($config->getValue("reportMail.smtp.encryption"));
}

if ($config->isValueSet("reportMail.smtp.username"))
{
	$transport->setUsername($config->getValue("reportMail.smtp.username"));
}

if ($config->isValueSet("reportMail.smtp.password"))
{
	$transport->setPassword($config->getValue("reportMail.smtp.password"));
}

$mailer = Swift_Mailer::newInstance($transport);

$attachment = Swift_Attachment::fromPath($tempFile);
$attachment->setFilename($reportInstance->getOutputFilename());

$message = Swift_Message::newInstance();

$message->setSubject($config->getValue("reportMail.subject"));
$message->setFrom($config->getValue("reportMail.from"));
$message->setTo($recipient);
$message->setBody($config->getValue("reportMail.body"));
$message->attach($attachment);

$mailer->send($message);

unlink($tempFile);