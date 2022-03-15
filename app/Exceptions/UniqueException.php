<?php
namespace PressToJamCore\Exceptions;

class UniqueException extends \Exception {

    private $error;

    function __construct($name, $value) {
        parent::__construct($name . ": " . $value . " already exists");
        $this->error = $name;
    }


    function getErrors() {
        return [$this->error => \PressToJamCore\Cells\ValidationRules::Unique];
    }



}