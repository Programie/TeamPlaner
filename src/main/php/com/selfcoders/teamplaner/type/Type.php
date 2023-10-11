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
     * @var string[]
     */
    public $style;
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
        $this->style = $data->style ?? null;
        $this->showInReport = isset($data->showInReport) ? ((bool)$data->showInReport) : false;
        $this->noSave = isset($data->noSave) ? ((bool)$data->noSave) : false;
    }
}