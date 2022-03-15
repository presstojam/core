<?php

namespace PressToJamCore\Cells;

class CellStates {
    public const read = 1;
    public const write = 2;
    public const filter = 3;
    public const group = 4;
    public const order_asc = 5;
    public const order_desc = 6;
    public const encrypted = 7;
}


class CellValueType {
    public const fixed = 0;
    public const range = 1;
    public const set = 2;
}

class Cell {

    protected $states = [];
    protected $value = null;

    private $max = null;
    private $min = null;
    private $contains = "";
    private $not_contains = "";

    

    function __set($name, $value) {
        if (property_exists($this, $name)) $this->$name = $value;
    }

    function __get($name) {
        if (property_exists($this, $name)) return $this->$name;
        else return null;
    }
    
    function __toString() {
        return $this->value;
    }

    //values coming from a database. important for number cell
    function mapOutput($val) {
        $this->value = $val;
    }
    
    function hasState($state) {
        return (in_array($state, $this->states));
    }

    function hasEitherStates($states) {
        foreach ($states as $state) {
            if (in_array($state, $this->states)) {
                return true;
            }
        }
        return false;
    }


    function addState($state) {
        if (!in_array($state, $this->states)) $this->states[] = $state;
    }

    function removeStates() {
        $this->states = [];
    }


    function removeState($state) {
        $pos = array_search($state, $this->states);
        if ($pos !== false) array_splice($this->states, $pos, 1);
    }

    function isOn() {
        return (count($this->states) == 0) ? false : true;
    }

    function getType() {
        if (is_array($this->value)) {
            if (isset($this->value["min"]) OR isset($this->value["max"])) return CellValueType::range;
            else return CellValueType::set;
        } else {
            return CellValueType::fixed;
        }
    }

    function export($func = null) {
        $fvals;
        if (is_array($this->value)) {
            $fvals = [];
            foreach($this->value as $key=>$val) {
                $fvals[$key] = ($func) ? $func($val) : $val;
            }
        } else {
            $fvals = ($func) ? $func($this->value) : $this->value;
        }   
        return $fvals;
    }

    function reset() {
        $this->value = null;
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

    function schema() {
        return array("min"=>$this->min, "max"=>$this->max, "contains"=>$this->contains, "notcontains"=>$this->not_contains);
    }

 
}