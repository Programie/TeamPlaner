<?php
namespace com\selfcoders\teamplaner;

use DateTime;

class Holiday
{
    /**
     * @var DateTime
     */
    public $date;
    /**
     * @var string
     */
    public $title;

    public function __construct(DateTime $date, $title)
    {
        $this->date = $date;
        $this->title = $title;
    }
}