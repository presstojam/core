<?php

namespace PressToJamCore;


class RoutePoint implements \JsonSerializable {

    protected $name;
    protected $parent;
    protected $children = [];
    protected $has_edit = true;
    protected $has_create = true;
    protected $has_delete = true;
    protected $reverse_refs = [];
    

    function __construct($name) {
        $this->name = $name;
    }

    function __set($name, $val) {
        if (property_exists($this, $name)) $this->$name = $val;
    }


    function __get($name) {
        if (property_exists($this, $name)) return $this->$name;
    }


    function addChild($child) {
        $this->children[] = $child;
    }

    function addReverseRef($ref) {
        $this->reverse_refs[] = $ref;
    }
   

    function jsonSerialize() {
        $perms=[];
        if ($has_edit) $perms[] = "put";
        if ($has_create) $perms[] = "create";
        if ($has_delete) $perms[] = "delete";

        $arr=[
            "name"=>$this->name, 
            "parent"=>$this->parent, 
            "perms"=>$this->perms, 
            "children"=>$this->children, 
            "reverse_refs"=>$this->reverse_refs
        ];
        return $arr;
    }
}