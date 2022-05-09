<?php
namespace PressToJamCore\Exceptions;

use \Slim\Exception\HttpSpecializedException;

class UserException extends \HttpSpecializedException {

    protected $code = "401";
    protected $title = "User Authentication Failed";
    protected $description = "User Authentication Failed";
    protected $message = "";

    function __construct($code, $message) {
        $this->code = $code;
        $this->message = $message;
    }

}