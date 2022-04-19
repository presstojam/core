<?php

namespace PressToJamCore\Cells;

abstract class CellValueType
{
    const range = 0;
    const min = 1;
    const max = 2;
    const set = 3;
    const fixed = 4;
}


class MetaCell {

    protected $max = null;
    protected $min = null;
    protected $contains = "";
    protected $not_contains = "";
    protected $name;
    protected $type = CellValueType::fixed;
    protected $default;
    protected $label;
    
    protected $summary = false;


    function __construct() {
        
    }

    function __set($name, $value) {
        if (property_exists($this, $name)) $this->$name = $value;
    }

    function __get($name) {
        if (property_exists($this, $name)) return $this->$name;
        else return null;
    }
 

    function setType($data) {
        $this->type = CellValueType::fixed;
    }

    
    function validateSize($size) {
        if ($this->min !== null AND $size < $this->min) {
            return ValidationRules::OutOfRangeMin;
        } else if ($this->max !== null AND $size > $this->max) {
            return ValidationRules::OutOfRangeMax;
        }
    }


    function validateValue($value) {
        if ($this->contains != "" AND !preg_match("/" . $this->contains . "/", $value)) {
            return ValidationRules::Characters;
        } else if ($this->not_contains != "" AND preg_match("/" . $this->not_contains . "/", $value)) {
            return ValidationRules::CharactersNegative;
        }
        return ValidationRules::OK;
    }


    function mapToStmtFilter($col) {
        return $col . " = ?";
    }

    function export($val) {
        return $val;
    }

    function toArg($val) {
        return $val;
    }


    function toSchema() {
        $arr=[];
        $arr["validation"] = [
            "min"=>$this->min, 
            "max"=>$this->max, 
            "contains"=>$this->contains, 
            "notcontains"=>$this->notcontains
        ];
        $arr["label"] = $this->label;
        $arr["title"] = $this->label;
        if ($this->default) $arr["default"] = $this->default;
        if ($this->summary) $arr["summary"] = true;

        return $arr;
    }
 
}