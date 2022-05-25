<?php

namespace PressToJamCore;

class MetaCollection {

    protected $slug = "";
    protected $table = "";
    protected $alias;
    protected $model;

    static protected $num = 1;
    
    
    function __construct($slug, $table, $model) {
        $this->slug = $slug;
        $this->table = $table;
        $this->model = $model;
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