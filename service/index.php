<?php
use com\selfcoders\teamplaner\auth\UserAuthFactory;
use com\selfcoders\teamplaner\BackendHandler;
use com\selfcoders\teamplaner\Config;
use com\selfcoders\teamplaner\DBConnection;
use com\selfcoders\teamplaner\service\exception\EndpointNotFoundException;
use com\selfcoders\teamplaner\service\exception\ServiceConfigurationException;

require_once __DIR__ . "/../bootstrap.php";

$config = new Config();

$pdo = DBConnection::getConnection($config);

$userAuthInstance = UserAuthFactory::getProvider($config->getValue("userAuth"));
if (!$userAuthInstance)
{
	header("HTTP/1.1 500 Internal Server Error");
	echo "Unable to load User Auth provider!";
	exit;
}

$token = isset($_GET["token"]) ? $_GET["token"] : null;
if ($token !== null)
{
	$query = $pdo->prepare("
		SELECT `id`, `username`
		FROM `users`
		WHERE `token` = :token
	");

	$query->execute(array
	(
		":token" => $token
	));

	if ($query->rowCount())
	{
		$row = $query->fetch();

		$userAuthInstance->authorizeUserById($row->id, $row->username);
	}
}

if (!$userAuthInstance->checkAuth())
{
	header("HTTP/1.1 401 Unauthorized");
	echo "You have to authenticate first!";
	exit;
}

if (!$userAuthInstance->checkPermissions())
{
	header("HTTP/1.1 403 Forbidden");
	echo "You are not allowed to access this service!";
	exit;
}

$handler = new BackendHandler($config, $userAuthInstance);

try
{
	$response = $handler->handleRequest($_SERVER["PATH_INFO"], $_SERVER["REQUEST_METHOD"], json_decode(file_get_contents("php://input")));
	if ($response !== null)
	{
		header("Content-Type: application/json");
		echo json_encode($response);
	}
}
catch (EndpointNotFoundException $exception)
{
	header("HTTP/1.1 404 Not Found");
	echo "The requested endpoint '" . $exception->getPath() . "' (" . $exception->getMethod() . ") does not exist!";
}
catch (ServiceConfigurationException $exception)
{
	header("HTTP/1.1 500 Internal Server Error");
	echo "Error in endpoint configuration!";
}
catch (Exception $exception)
{
	header("HTTP/1.1 500 Internal Server Error");
	echo "Error while executing method! (" . $exception->getMessage() . ")";
}