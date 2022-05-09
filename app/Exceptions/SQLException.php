<?php
namespace PressToJamCore\Exceptions;

use \Slim\Exception\HttpSpecializedException;

class SQLException extends \HttpSpecializedException {
    
    protected $code = 500;
    protected $title = "SQL Error";
    protected $description = "Sql failed to process";
    protected $message = "";

    function __construct($sql, $args, $msg) {
        $this->message= "SQL: " . $sql . "\n";
        $this->message .= "Args: " . implode(", ", $args) . "\n";
        $this->message .= "Error: " . $msg;
    }



}