<?php

namespace PressToJamCore;

class GCDate {

    private $date_format;
    private $time_format;
    private $timezone;

    function __construct(Configs\Date $date) {
        $this->date_format = $date->time_format;
        $this->time_format = $date->date_format;
        $htis->timezone = $date->timezone;
    }

    function getPHPFormat($contains) {

    }


    function getMYSQLFormat($contains) {

    }
}