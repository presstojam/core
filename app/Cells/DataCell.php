<?php

namespace PressToJamCore\Cells;


class DataCell {

    protected $value = null;
    protected $meta_field = null;

    function __construct($field) {
        $this->meta_field = $field;
        $this->value = $this->meta_field->default;
    }
    

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
        $this->value = $this->meta_field->mapOutput($val);
    }
    

    function getType() {
        if (is_array($this->value)) {
            if (isset($this->value["min"]) OR isset($this->value["max"])) return CellValueType::range;
            else return CellValueType::set;
        } else {
            return CellValueType::fixed;
        }
    }

    function export() {
        $fvals;
        if (is_array($this->value)) {
            $fvals = [];
            foreach($this->value as $key=>$val) {
                $fvals[$key] = $this->meta_field->export($val);
            }
        } else {
            $fvals = $this->meta_field->export($this->value);
        }   
        return $fvals;
    }

    function reset() {
        $this->value = null;
    }

    function validate() {
        return $this->meta_field->validate($this->value);
    }

  

    function schema() {
        return $this->meta_field->schema();
    }

 
}