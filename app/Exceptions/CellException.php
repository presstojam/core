<?php
namespace PressToJamCore\Exceptions;

class CellException extends \Exception {

    protected $code = "500";
    protected $message = "";
    protected $title = "Field doesn't exist";
    protected $description "Trying to access field that doesn't exist";
   

    function __construct($model_name, $name) {
        $this->message = "Field: " . $model_name . "::" . $name . " doesn't exist";
    }

    function __get($key) {
        if (property_exist($this, $key)) return $this->$key;
    }


}