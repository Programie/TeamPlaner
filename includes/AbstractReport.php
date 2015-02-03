<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/iReport.php";

abstract class AbstractReport implements iReport
{
	/**
	 * @var Config
	 */
	protected $config;
	/**
	 * @var PDO
	 */
	protected $pdo;
	/**
	 * @var int The year of the report
	 */
	protected $year;
	/**
	 * @var int The month of the report
	 */
	protected $month;
	/**
	 * @var string The path to the filename in which the report should be written (e.g. php://stdout, php://output or some real file path)
	 */
	protected $output;
	/**
	 * @var string The content type of the generated report
	 */
	protected $outputContentType;
	/**
	 * @var string The filename of the generated report (used as the filename in the download and mail attachment)
	 */
	protected $outputFilename;

	/**
	 * @param Config $config Instance of Config class containing the data loaded from config.json
	 */
	public function setConfig(Config $config)
	{
		$this->config = $config;
	}

	/**
	 * @param PDO $pdo Instance of PDO class connected to the database
	 */
	public function setPDO(PDO $pdo)
	{
		$this->pdo = $pdo;
	}

	/**
	 * @param int $year Set the year of which the report should be generated
	 */
	public function setYear($year)
	{
		$this->year = $year;
	}

	/**
	 * @param int|null $month Set the month of which the report should be generated (null = all months of the year)
	 */
	public function setMonth($month)
	{
		$this->month = $month;
	}

	/**
	 * @return int The year of which the report should be generated
	 */
	public function getYear()
	{
		return $this->year;
	}

	/**
	 * @return int|null The month of which the report should be generated (null = all months of the year)
	 */
	public function getMonth()
	{
		return $this->month;
	}

	/**
	 * @return string The content type of the generated report
	 */
	public function getOutputContentType()
	{
		return $this->outputContentType;
	}

	/**
	 * @return string The filename of the generated report (used as the filename in the download and mail attachment)
	 */
	public function getOutputFilename()
	{
		return $this->outputFilename;
	}

	/**
	 * @param string $output The path to the filename in which the report should be written (e.g. php://stdout, php://output or some real file path)
	 */
	public function setOutput($output)
	{
		$this->output = $output;
	}
}