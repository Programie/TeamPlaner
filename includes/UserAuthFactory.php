<?php
require_once __DIR__ . "/DefaultUserAuth.php";

class UserAuthFactory
{
	/**
	 * Get a new instance of the given user auth provider
	 *
	 * @param string $name the full class name including the namespace to the user auth provider class. This must be a path in the extensions folder!
	 *
	 * @return iUserAuth|null
	 * @throws Exception
	 */
	public static function getProvider($name = null)
	{
		if ($name == null or $name == "")
		{
			return new DefaultUserAuth();
		}

		$filename = __DIR__ . "/../extensions/" . $name . ".php";
		if (!file_exists($filename))
		{
			throw new Exception("No such file or directory: " . $filename);
		}

		require_once $filename;

		$classname = str_replace("/", "\\", $name);

		if (!class_exists($classname))
		{
			throw new Exception("Class not found: " . $classname);
		}

		return new $classname;
	}
}