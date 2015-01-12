<?php
interface iReport
{
	/**
	 * @param Config $config Instance of Config class containing the data loaded from config.json
	 */
	public function setConfig(Config $config);

	/**
	 * @param PDO $pdo Instance of PDO class connected to the database
	 */
	public function setPDO(PDO $pdo);

	/**
	 * Called once the report should be created.
	 * This method should print the data (e.g. using "echo").
	 *
	 * @param int $year The year of the report
	 * @param int $month The month of the report
	 */
	public function create($year, $month);
}