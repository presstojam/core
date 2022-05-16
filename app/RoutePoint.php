<?php

namespace PressToJamCore;


class RoutePoint implements \JsonSerializable {

    protected $name;
    protected $title;
    protected $parent;
    protected $children = [];
    protected $perms = [];
    protected $refs = [];
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

    function addRef($ref) {
        $this->refs[] = $ref;
    }

    function addPerm($perm) {
        $this->perms[] = $perm;
    }
   
    function jsonSerialize() {
        $arr=[
            "name"=>$this->name, 
            "title"=>$this->title,
            "parent"=>$this->parent, 
            "perms"=>$this->perms, 
            "children"=>$this->children, 
            "refs"=>$this->refs,
            "reverse_refs"=>$this->reverse_refs
        ];
        return $arr;
    }
}