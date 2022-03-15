<?php

namespace PressToJamCore;

class Data {

    private $fields=array();

    function __construct($fields) {
        foreach ($fields as $field) {
            $this->fields[$field] = null;
        }
    }

    function __set($name, $value) {
        if (array_key_exists($name, $this->fields)) {
            if ($this->fields[$name] == null) {
                $this->fields[$name] = new Cell($value);
            } else {
                $this->fields[$name]->map($value);
            }
        }
    }

    function __get($name) {
        if (array_key_exists($name, $this->fields)) return $this->fields[$name];
        else return null;
    }

    function map($data) {
        foreach($data as $key=>$val) {
            $this->__set($key, $val);
        }
    }

    function toArr() {
        $arr=array();
        foreach($this->fields as $key=>$val) {
            if ($val !== null) $arr[$key] = $val;
        }
        return $arr;
    }
}