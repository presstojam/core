<?php

namespace PressToJamCore\Cells;

class NumberCell extends Cell {


    function __get($name) {
        if ($name == "param_str") return \PDO::PARAM_INT;
        else if (property_exists($this, $name)) return $this->$name;
        else return null;
    }
    
    function __toString() {
        if (is_array($this->value)) {

        }
        return (string) $this->value;
    }

    function setValidation($min, $max) {
        $this->min = $min;
        $this->max = $max;
    }

    function mapOutput($val) {
        $this->value = (int) $val;
    }

    function map($value) {
        if (is_array($value)) {
            foreach($value as $key=>$val) {
                $value[$key] = (is_numeric($val)) ? $val : 0;
            }
        } else {
            $value = (is_numeric($value)) ? $value : 0;
        }
        $this->value = $value;
    }


    function validate() {
        if (!$this->isOn()) return ValidationRules::OK;
        
        if (is_array($this->value)) {
            foreach ($this->value as $val) {
                $rule = $this->validateSize($val);
                if ($rule != ValidationRules::OK) {
                    return $rule;
                }
            }
        } else {         
            $rule = $this->validateSize($this->value);         
            if ($rule != ValidationRules::OK) {
                return $rule;
            }
        }

        return ValidationRules::OK;
    }

}