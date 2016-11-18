<?php
namespace com\selfcoders\teamplaner\service;

use com\selfcoders\teamplaner\ExtensionClassFactory;
use com\selfcoders\teamplaner\Holiday;

class Holidays extends AbstractService
{
    public function getList()
    {
        $holidays = array();

        if ($this->config->isValueSet("holidaysMethod")) {
            list($className, $methodName) = explode("#", $this->config->getValue("holidaysMethod"));

            $holidaysInstance = ExtensionClassFactory::getInstance($className);

            if (method_exists($holidaysInstance, $methodName)) {
                /**
                 * @var $holiday Holiday
                 */
                foreach ($holidaysInstance->$methodName($this->parameters->year) as $holiday) {
                    $holidays[$holiday->date->format("Y-m-d")] = $holiday->title;
                }
            }
        }

        return $holidays;
    }
}