<?php
namespace PressToJamCore\Exceptions;

class SQLException extends \Exception {

    function __construct($sql, $args, $msg) {
        $str = "SQL: " . $sql . "\n";
        $str .= "Args: " . implode(", ", $args) . "\n";
        $str .= "Error: " . $msg;
        parent::__construct($str);
    }



}