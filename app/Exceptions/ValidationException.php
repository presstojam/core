<?php
namespace PressToJamCore\Exceptions;

use \Slim\Exception\HttpSpecializedException;

class ValidationException extends \HttpSpecializedException {

    protected $code = "500";
    protected $title = "Validation Errors";
    protected $description = "Validation Errors";
    protected $message = "";

    function __construct($errors) {
        $this->message = json_encode($errors);
    }
}