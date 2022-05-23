<?php
namespace PressToJamCore;

class ArchiveHandler {

    protected $pdo;

    function __construct($pdo) {
        $this->pdo = $pdo;
    }

}