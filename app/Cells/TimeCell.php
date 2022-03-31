<?php

namespace PressToJamCore\Cells;

class TimeCell extends Cell {

    function __get($name) {
        if ($name == "param_str") return \PDO::PARAM_STR;
        else if (property_exists($this, $name)) return $this->$name;
        else return null;
    }
 

    function getValues() {
        if (!$this->is_range) return $this->value;
        else {
            $arr=[];
            if ($this->value !== null) $arr[] = $this->value;
            if ($this->max !== null) $arr[] = $this->max;
            return $arr;
        }
    }

    function setValidation($min, $max) {
        $this->min = $min;
        $this->max = $max;
    }


    function map($val) {
        $this->value = $val;
    }

    function getTimestamp($date) {
        $d = \DateTime::createFromFormat('Y-m-d\TH:i', $date);
        if (!$d) {
            throw new \Exception("Datetime could not be created from date: " . $date);
        }
        return $d->getTimestamp();
    }


    function validate() {
        if (!$this->isOn()) return ValidationRules::OK;

        if (is_array($this->value)) {
            foreach($this->value as $key=>$val) {
                $rule = $this->validateSize($this->getTimestamp($val));
                if ($rule != ValidationRules::OK) {
                    return $rule;
                }
            }
        } else {
            $rule = $this->validateSize($this->getTimestamp($this->value));
            if ($rule != ValidationRules::OK) {
                return $rule;
            }
        }
       
        return ValidationRules::OK;
    }

    function reset() {
        $this->value = null;
        $this->max = null;
        $this->is_range = false;
    }

}