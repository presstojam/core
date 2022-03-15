<?php

namespace PressToJamCore\Configs;

class Date {

    public $date_format;
    public $time_format;
    public $timezone;

    function toArr() {
        return ["php_format"=>$this->php_format, "timezone"=>$this->timezone, "sql_format"=>$this->sql_format];
    }

}