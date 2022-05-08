<?php
namespace PressToJamCore\Exceptions;

class UserException extends \Exception {

    function __construct($msg) {
        parent::__construct("Authorisation failure: " . $msg);
    }

}