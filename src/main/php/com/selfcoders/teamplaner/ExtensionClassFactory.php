<?php
namespace com\selfcoders\teamplaner;

use Exception;

class ExtensionClassFactory
{
    /**
     * Get a new instance of the given class in the given extension.
     *
     * The name should be in the format "extension/ClassName" which is also the namespace.
     *
     * @param string $name the full class name including the namespace to the class. This must be a path in the extensions folder!
     *
     * @return mixed
     * @throws Exception
     */
    public static function getInstance($name)
    {
        $filename = APP_ROOT . "/extensions/" . $name . ".php";
        if (!file_exists($filename)) {
            throw new Exception("No such file or directory: " . $filename);
        }

        require_once $filename;

        $class = str_replace("/", "\\", $name);

        if (!class_exists($class)) {
            throw new Exception("Class not found: " . $class);
        }

        return new $class;
    }
}