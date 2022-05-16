<?php
namespace PressToJamCore;

class Profile {

    protected $permissions = [];


    function hasPermission($model, $state, $method) {
        if (!isset($this->permissions[$model])) return false;
        if (!isset($this->permissions[$model][$state])) return false;
        if ($this->permissions[$model][$state] != $method) return false;
        return true;
    }
}