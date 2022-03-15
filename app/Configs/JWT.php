<?php

namespace PressToJamCore\Configs;

class JWT {

    public $secret;

    function toArr() {
        return ["secret"=>$this->secret];
    }

}