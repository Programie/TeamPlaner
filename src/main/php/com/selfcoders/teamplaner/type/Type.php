<?php
namespace com\selfcoders\teamplaner\type;

use stdClass;

class Type
{
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var string
	 */
	public $title;
	/**
	 * @var string
	 */
	public $color;
	/**
	 * @var bool
	 */
	public $showInReport;
	/**
	 * @var bool
	 */
	public $noSave;

	public function __construct(StdClass $data)
	{
		$this->name = $data->name;
		$this->title = $data->title;
		$this->color = $data->color;
		$this->showInReport = (bool) $data->showInReport;
		$this->noSave = (bool) $data->noSave;
	}
}