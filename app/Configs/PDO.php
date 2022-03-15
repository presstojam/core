<?php

namespace PressToJamCore\Configs;

class PDO {

    public $host;
    public $name;
    public $user;
    public $pass;
    public $port = null;
    public $cert;

    function toArr() {
        return [
            "host"=>$this->host,
            "name"=>$this->name,
            "user"=>$this->user,
            "pass"=>$this->pass,
            "port"=>$this->port,
            "cert"=>$this->cert
        ];
    }
}