<?php

namespace PressToJamCore;

class MetaCollection {

    protected $slug = "";
    protected $table = "";
    protected $alias;

    static protected $num = 1;
    
    
    function __construct($slug, $table) {
        $this->slug = $slug;
        $this->table = $table;
        $this->alias = "t" . self::$num;
        ++self::$num;
    }


    function __get($key) {
        if (property_exists($this, $key)) return $this->$key;
    }

    function __set($key, $val) {
        if (property_exists($this, $key)) $this->$key = $val;
    }


    function hasOwner() {
        return method_exists($this, "owner");
    }

    function hasParent() {
        return method_exists($this, "parent");
    }
}