<?php
namespace PressToJamCore\Exceptions;

class DataMapException extends \Exception {

    function __construct($object_name, $name, $value, $states) {
        parent::__construct("Incorrect permissions when trying to map a value to " . $name . " in " . $object_name . ". Value is " . $value . ". States: " . implode($states));
    }



}