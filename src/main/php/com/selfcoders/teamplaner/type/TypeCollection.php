<?php
namespace com\selfcoders\teamplaner\type;

class TypeCollection
{
	/**
	 * @var array
	 */
	private $types;

	public function __construct($data)
	{
		$this->types = array();

		foreach ($data as $type)
		{
			$type = new Type($type);

			$this->types[$type->name] = $type;
		}
	}

	/**
	 * Get all types.
	 *
	 * @return array An array of types
	 */
	public function getTypes()
	{
		return $this->types;
	}

	/**
	 * Get all types with the showInReport property set to true.
	 *
	 * @return array An array of types
	 */
	public function getReportTypes()
	{
		$types = array();

		/**
		 * @var $type Type
		 */
		foreach ($this->types as $index => $type)
		{
			if (!$type->showInReport)
			{
				continue;
			}

			$types[$index] = $type;
		}

		return $types;
	}

	/**
	 * Get the type with the specified name.
	 *
	 * @param string $name The name of the type
	 *
	 * @return Type|null The type or null if not found
	 */
	public function getTypeByName($name)
	{
		if (!isset($this->types[$name]))
		{
			return null;
		}

		return $this->types[$name];
	}
}