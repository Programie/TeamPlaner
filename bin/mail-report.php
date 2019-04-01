<?php
use com\selfcoders\teamplaner\Config;
use com\selfcoders\teamplaner\DBConnection;
use com\selfcoders\teamplaner\ExtensionClassFactory;
use com\selfcoders\teamplaner\report\iReport;

require_once __DIR__ . "/../bootstrap.php";

if (count($argv) < 4) {
    echo "Usage: " . $argv[0] . " <recipient> <teams> <year> [<month>]";
    exit;
}

$recipient = @$argv[1];
$teams = @$argv[2];
$year = @$argv[3];
$month = @$argv[4];

if (!$month) {
    $month = null;
}

$config = new Config();

if (!$config->isValueSet("reportClass")) {
    echo "Report class not defined!";
    exit;
}

$pdo = DBConnection::getConnection($config);

$teamsQuery = $pdo->prepare("
    SELECT `id`, `title`
    FROM `teams`
    WHERE `name` = :name");

$transport = Swift_SmtpTransport::newInstance();

if ($config->isValueSet("reportMail.smtp.host")) {
    $transport->setHost($config->getValue("reportMail.smtp.host"));
}

if ($config->isValueSet("reportMail.smtp.port")) {
    $transport->setPort($config->getValue("reportMail.smtp.port"));
}

if ($config->isValueSet("reportMail.smtp.encryption")) {
    $transport->setEncryption($config->getValue("reportMail.smtp.encryption"));
}

if ($config->isValueSet("reportMail.smtp.username")) {
    $transport->setUsername($config->getValue("reportMail.smtp.username"));
}

if ($config->isValueSet("reportMail.smtp.password")) {
    $transport->setPassword($config->getValue("reportMail.smtp.password"));
}

$mailer = Swift_Mailer::newInstance($transport);

$message = Swift_Message::newInstance();

$message->setFrom($config->getValue("reportMail.from"));
$message->setTo($recipient);
$message->setBody($config->getValue("reportMail.body"));

$teamTitles = [];
$tempFiles = [];

foreach (explode(",", $teams) as $team) {
    $teamsQuery->execute(array
    (
        ":name" => $team
    ));

    if (!$teamsQuery->rowCount()) {
        printf("Team %s not found! skipping...\n", $team);
    }

    $teamRow = $teamsQuery->fetch();

    $teamId = $teamRow->id;
    $teamTitles[] = $teamRow->title;

    $tempFile = tempnam(sys_get_temp_dir(), "calendar-report");
    $tempFiles[] = $tempFile;

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

    $attachment = Swift_Attachment::fromPath($tempFile);
    $attachment->setFilename($reportInstance->getOutputFilename());
    $message->attach($attachment);
}

$subject = $config->getValue("reportMail.subject");

$subject = str_replace("%team%", implode(", ", $teamTitles), $subject);// Replace %team% placeholder in mail subject with actual titles of the teams

$message->setSubject($subject);

// At least one temp file exists if a report has been attached to the message
if (!empty($tempFiles)) {
    $mailer->send($message);
}

// Cleanup
foreach ($tempFiles as $tempFile) {
    unlink($tempFile);
}