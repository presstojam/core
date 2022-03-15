<?php

namespace PressToJamCore\Configs;

class User {

    public $user;
    public $id;

    function toArr() {
        return ["user"=>$this->user, "id"=>$this->id];
    }

}