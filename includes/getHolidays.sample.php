<?php
// Copy this file to "getHolidays.php" and edit it to fit your needs.
// getHolidays() should return an array of dates which are holidays in the specified year.

/**
 * Get a list of all holidays in the specific year.
 *
 * @param int $year The year of which the holidays should be retrieved.
 *
 * @return array An array containing the dates in format "YYYY-MM-DD"
 */
function getHolidays($year)
{
	$easterSunday = easter_date($year);

	return array
	(
		$year . "-01-01",
		$year . "-01-06",
		date("Y-m-d", strtotime("-2 day", $easterSunday)),
		date("Y-m-d", strtotime("+1 day", $easterSunday)),
		$year . "-05-01",
		date("Y-m-d", strtotime("+39 day", $easterSunday)),
		date("Y-m-d", strtotime("+50 day", $easterSunday)),
		date("Y-m-d", strtotime("+60 day", $easterSunday)),
		$year . "-10-03",
		$year . "-11-01",
		$year . "-12-25",
		$year . "-12-26"
	);
}