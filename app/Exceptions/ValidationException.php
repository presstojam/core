<?php
namespace PressToJamCore\Exceptions;


class ValidationException extends \Exception {

    protected $code = "500";
    protected $title = "Validation Errors";
    protected $description = "Validation Errors";
    protected $message = "";

    function __construct($errors) {
        $this->message = $errors;
    }

    function __get($key) {
        if (property_exist($this, $key)) return $this->$key;
    }
}