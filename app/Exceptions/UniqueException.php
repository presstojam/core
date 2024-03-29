<?php
namespace PressToJamCore\Exceptions;


class UniqueException extends PtjException {

    protected $code = 500;
    protected $title = "Unique Exception Failure";
    protected $description = "Unique Exception Failure";
    protected $message = "";

    function __construct($field, $value) {
        $this->message = json_encode([$field => \PressToJamCore\Cells\ValidationRules::Unique]);
    }

}