<?php
namespace com\selfcoders\teamplaner\report;

use com\selfcoders\teamplaner\Config;
use PDO;

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
     * @param int $year Set the year of which the report should be generated
     */
    public function setYear($year);

    /**
     * @return int The year of which the report should be generated
     */
    public function getYear();

    /**
     * @param int|null $month Set the month of which the report should be generated (null = all months of the year)
     */
    public function setMonth($month);

    /**
     * @return int|null The month of which the report should be generated (null = all months of the year)
     */
    public function getMonth();

    /**
     * @param int $teamId Set the ID of the team of which the report should be generated
     */
    public function setTeamId($teamId);

    /**
     * @return string The content type of the generated report
     */
    public function getOutputContentType();

    /**
     * @return string The filename of the generated report (used as the filename in the download and mail attachment)
     */
    public function getOutputFilename();

    /**
     * @param string $output The path to the filename in which the report should be written (e.g. php://stdout, php://output or some real file path)
     */
    public function setOutput($output);

    /**
     * Configure the report (e.g. set output content type and output filename).
     * This method will be called before create().
     */
    public function configure();

    /**
     * Called once the report should be created.
     * Note: This method should write the data to the file specified in $output.
     */
    public function create();
}