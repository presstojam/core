<?php

namespace PressToJamCore;


class RoutePoint implements \JsonSerializable {

    protected $is_owner;
    protected $perms;
    protected $models=[];
    

    function __construct($name) {
        $this->name = $name;
    }

    function __set($name, $val) {
        if (property_exists($this, $name)) $this->$name = $val;
    }


    function __get($name) {
        if (property_exists($this, $name)) return $this->$name;
    }
    

    function jsonSerialize() : mixed {
        $arr=[
            "is_owner"=>$this->is_owner, 
            "perms"=>$this->perms,
            "models"=>$this->models 
        ];
        return $arr;
    }
}