<?php

namespace PressToJamCore\Cells;

class StringCell extends Cell {

    function __get($name) {
        if ($name == "param_type") return \PDO::PARAM_STR;
        else if (property_exists($this, $name)) return $this->$name;
        else return null;
    }
    
    function __toString() {
        return $this->value;
    }

    function setValidation($min, $max, $contains = "", $not_contains = "") {
        $this->min = $min;
        $this->max = $max;
        $this->contains = $contains;
        $this->not_contains = $not_contains;
    }



    function map($value) {
        if (is_array($value)) {
            foreach($value as $key=>$val) {
                $value[$key] = trim($val);
            }
            $this->value = $value;
        } else {
            $this->value = trim($value);
        }
    }



    function validate() {

        if (!$this->isOn()) return ValidationRules::OK;

        $validateVal = function($self, $val) {
            $rule = $self->validateSize(strlen($val));
            if ($rule != ValidationRules::OK) {
                return $rule;
            }
            
            $rule = ValidationRules::OK;
            if ($val) {
                $rule = $self->validateValue($val);
            }
            return $rule;
        };

        

        if (is_array($this->value)) {
            foreach ($this->value as $key=>$val) {
                $rule = $validateVal($this, $val);
                if ($rule != ValidationRules::OK) {
                    return $rule;
                }
            }
        } else {
            $rule = $validateVal($this, $this->value);
            if ($rule != ValidationRules::OK) {
                return $rule;
            }
        }
        return ValidationRules::OK;
    }


    function reset() {
        $this->value = null;
    }

}