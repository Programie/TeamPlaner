<?php
namespace com\selfcoders\teamplaner;

use com\selfcoders\teamplaner\auth\iUserAuth;
use com\selfcoders\teamplaner\router\Router;
use com\selfcoders\teamplaner\router\Target;
use com\selfcoders\teamplaner\service\AbstractService;
use com\selfcoders\teamplaner\service\exception\EndpointNotFoundException;
use com\selfcoders\teamplaner\service\exception\ServiceConfigurationException;
use ReflectionClass;
use ReflectionMethod;

class BackendHandler
{
	private $config;
	private $userAuth;

	public function __construct(Config $config, iUserAuth $userAuth)
	{
		$this->config = $config;
		$this->userAuth = $userAuth;
	}

	public function handleRequest($path, $method, $data)
	{
		$router = new Router();

		$router->map(HttpMethod::GET, "/user/token", new Target("User", "getToken"));

		$router->map(HttpMethod::GET, "/ical", new Target("ICal", "getData"));

		$router->map(HttpMethod::GET, "/report/data/[:team]/[i:year]?/[i:month]?", new Target("Report", "getData"));
		$router->map(HttpMethod::GET, "/report/download/[:team]/[i:year]?/[i:month]?", new Target("Report", "getDownload"));

		$router->map(HttpMethod::GET, "/entries/[:team]?/[i:year]?", new Target("Entries", "getAll"));

		$router->map(HttpMethod::PUT, "/entries/[:team]", new Target("Entries", "editMultiple"));

		// TODO: Split create (PUT) from update (POST)
		//$router->map(HttpMethod::POST, "/entries/[:team]", new Target("Entries", "createMultiple"));

		// TODO: Manage single entries
		//$router->map(HttpMethod::GET, "/entry/[i:id]", new Target("Entries", "get"));
		//$router->map(HttpMethod::PUT, "/entry/[i:id]", new Target("Entries", "edit"));
		//$router->map(HttpMethod::POST, "/entry", new Target("Entries", "create"));
		//$router->map(HttpMethod::DELETE, "/entry/[i:id]", new Target("Entries", "delete"));

		$match = $router->match($path, $method);
		if ($match === false)
		{
			throw new EndpointNotFoundException($path, $method);
		}

		/**
		 * @var $target Target
		 */
		$target = $match["target"];

		$classPath = "com\\selfcoders\\teamplaner\\service\\" . $target->class;

		if (!class_exists($classPath))
		{
			throw new ServiceConfigurationException("Configured class does not exist: " . $target->class);
		}

		$reflection = new ReflectionClass($classPath);

		if ($reflection->isAbstract())
		{
			throw new ServiceConfigurationException("Configured class is abstract: " . $target->class);
		}

		/**
		 * @var $serviceClassInstance AbstractService
		 */
		$serviceClassInstance = $reflection->newInstance($this->config, $this->userAuth, $method);

		if (!method_exists($serviceClassInstance, $target->method))
		{
			throw new ServiceConfigurationException("Configured method does not exist in " . $target->class . ": " . $target->method);
		}

		$serviceClassInstance->data = $data;
		$serviceClassInstance->parameters = (object) $match["params"];

		$reflectionMethod = new ReflectionMethod($serviceClassInstance, $target->method);
		return $reflectionMethod->invoke($serviceClassInstance);
	}
}