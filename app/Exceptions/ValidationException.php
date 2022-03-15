<?php
namespace PressToJamCore\Exceptions;

class ValidationException extends \Exception {

    protected $errors;

    function __construct($errors) {
        parent::__construct("A validation error has occured");
        $this->errors = $errors;
    }


    function getErrors() {
        return $this->errors;
    }

}