<?php
require_once __DIR__ . "/../includes/DBConnection.php";

if (count($argv) < 3)
{
	die("Usage: " . $argv[0] . " <year> <month> [<type1,type2,type3,...>]");
}

$weekdays = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");

$pdo = DBConnection::getConnection();

$year = $argv[1];
$month = $argv[2];

if (isset($argv[3]))
{
	$types = explode(",", $argv[3]);
}
else
{
	$types = null;
}

if (!checkdate($month, 1, $year))
{
	die("The given year and month is invalid!");
}

$data = array();

$query = $pdo->prepare("
	SELECT `date`, `type`, `userId`
	FROM `entries`
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
	if ($types and !in_array($row->type, $types))
	{
		continue;
	}

	$data[$row->userId][$row->date] = $row->type;
}

$file = fopen("php://stdout", "w");

$query = $pdo->query("SELECT `id`, `username` FROM `users`");

while ($row = $query->fetch())
{
	fputcsv($file, array("User", $row->username), ";");

	fputcsv($file, array("Date", "Week day"), ";");

	foreach ($data[$row->id] as $date => $type)
	{
		fputcsv($file, array($date, $weekdays[date("w", strtotime($date))], $type), ";");
	}
}

fclose($file);