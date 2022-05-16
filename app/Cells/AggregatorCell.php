<?php

namespace PressToJamCore\Cells;


class AggregatorCell {

    protected $value = null;
    protected $meta_field = null;
    protected $alias;

    function __construct($field) {
        $this->meta_field = $field;
        $this->value = $this->meta_field->default;
    }
    

    function __set($name, $value) {
        if (property_exists($this, $name)) $this->$name = $value;
    }

    function __get($name) {
        if (property_exists($this, $name)) return $this->$name;
        else return $this->meta_field->$name;
    }

    function __call($name, $args) {
        if (!$args) $args=[$this->value];
        return call_user_func_array([$this->meta_field, $name], $args);
    }
    
    function __toString() {
        return $this->value;
    }

    //values coming from a database. important for number cell
    function mapOutput($val) {
        $this->value = $this->meta_field->mapOutput($val);
    }
    

    function setType() {
        if (is_array($this->value)) {
            if (isset($this->value['min']) AND isset($this->value['max'])) $this->meta_field->type = CellValueType::range;
            else if (isset($this->value['min'])) $this->meta_field->type = CellValueType::min;
            else if (isset($this->value['max'])) $this->meta_field->type = CellValueType::max;
            else $this->meta_field->type = CellValueType::set;
        } else {
            $this->meta_field->type = CellValueType::fixed;
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

    function map($val) {
        $this->value = $this->meta_field->map($val);
    }

 
}