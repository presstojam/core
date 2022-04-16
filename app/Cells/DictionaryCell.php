<?php

namespace PressToJamCore\Cells;

class DictionaryCell {

    protected $label = "";
    protected $hint = null;
    protected $placeholder = null;
    

    function __get($name) {
        if (property_exists($this, $name)) return $this->$name;
    }


    function __set($name, $val) {
        if (property_exists($this, $name)) $this->$name = $val;
    }

    function toArr() {
        $arr=[];
        $arr["label"] = $this->label;
        if ($this->hint) {
            $arr["hint"] = $this->hint;
        }
        if ($this->placeholder) {
            $arr["placeholder"] = $this->placeholder;
        }
        return $arr;
    }
 
}