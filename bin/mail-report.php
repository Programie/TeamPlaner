<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../includes/DBConnection.php";
require_once __DIR__ . "/../includes/Config.php";
require_once __DIR__ . "/../includes/ExtensionClassFactory.php";

if (count($argv) < 3)
{
	echo "Usage: " . $argv[0] . " <recipient> <year> [<month>]";
	exit;
}

$recipient = @$argv[1];
$year = @$argv[2];
$month = @$argv[3];

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

$message = Swift_Message::newInstance();

$message->setSubject($config->getValue("reportMail.subject"));
$message->setFrom($config->getValue("reportMail.from"));
$message->setTo($recipient);
$message->setBody($config->getValue("reportMail.body"));
$message->attach(Swift_Attachment::fromPath($tempFile));

$mailer->send($message);

unlink($tempFile);