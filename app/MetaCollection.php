<?php

namespace PressToJamCore;

class MetaCollection {

    protected $slug = "";
    protected $alias;
    protected $fields = [];
    protected $parent = null;
    protected $primary = null;
    protected $owner = null;
    protected $archive = null;
    protected $date_created = null;
    protected $last_updated = null;
    protected $sort;
    
    static protected $num = 1;
    
    function __construct() {
        $this->alias = "t" . self::$num;
        ++self::$num;
    }


    function __get($key) {
        if (property_exists($this, $key)) return $this->$key;
    }

    function __set($key, $val) {
        if (property_exists($this, $key)) $this->$key = $val;
    }


}