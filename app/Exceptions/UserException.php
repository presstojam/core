<?php
namespace PressToJamCore\Exceptions;


class UserException extends \Exception {

    protected $code = "401";
    protected $title = "User Authentication Failed";
    protected $description = "User Authentication Failed";
    protected $message = "";

    function __construct($code, $message) {
        $this->code = $code;
        $this->message = $message;
    }

    function __get($key) {
        if (property_exist($this, $key)) return $this->$key;
    }

}