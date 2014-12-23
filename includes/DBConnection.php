<?php
require_once __DIR__ . "/Config.php";

class DBConnection
{
	/**
	 * @var PDO
	 */
	private static $pdo;

	public static function getConnection(Config $config = null)
	{
		if (DBConnection::$pdo)
		{
			return DBConnection::$pdo;
		}

		if (!$config)
		{
			$config = new Config();
		}

		DBConnection::$pdo = new PDO($config->getValue("database.dsn"), $config->getValue("database.username"), $config->getValue("database.password"));

		DBConnection::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		DBConnection::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

		DBConnection::$pdo->query("SET NAMES utf8");

		return DBConnection::$pdo;
	}
}