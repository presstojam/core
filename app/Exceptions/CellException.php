<?php
namespace PressToJamCore\Exceptions;

use \Slim\Exception\HttpSpecializedException;

class CellException extends \HttpSpecializedException {

    protected $code = 500;
    protected $title = "Field doesn't exist";
    protected $description = "Field doesn't exist";
    protected $message = "Field: ";
   

    function __construct($model_name, $name) {
        $this->message .= $model_name . "::" . $name . " doesn't exist";
    }



}