<?php
namespace PressToJamCore;

class Mask {
    private $data_cols = array(); //numerical array of data cols
    private $filter_cols = array(); //associative array of col_name => value
    private $order_cols=array(); //numerical array of cols
    private $limit = -1;
    private $offset = 0;

    function __set($name, $value) {
        if (property_exists($this, $name)) $this->{$name} = $value;
    }

    function __get($name) {
        if (property_exists($this, $name)) return $this->{$name};
        else return null;
    }

    function import($arr) {
        foreach($arr as $name=>$val) {
            if (property_exists($this, $name)) $this->{$name} = $value;
        }
    }

    function fieldOn($field) {
        if (in_array($field, $this->data_cols)) return true;
        else if (isset($this->filter[$field])) return true;
        else return false;
    }
}