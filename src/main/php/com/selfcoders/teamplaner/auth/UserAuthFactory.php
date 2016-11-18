<?php
namespace com\selfcoders\teamplaner\auth;

use com\selfcoders\teamplaner\ExtensionClassFactory;
use Exception;

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
        if ($name == null or $name == "") {
            return new DefaultUserAuth();
        }

        return ExtensionClassFactory::getInstance($name);
    }
}