<?php
namespace PressToJamCore\Exceptions;

class CellException extends \Exception {

    function __construct($object_name, $name) {
        parent::__construct("Field " . $name . " doesn't exist in model " . $object_name);
    }



}