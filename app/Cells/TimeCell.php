<?php

namespace PressToJamCore\Cells;

class TimeCell extends MetaCell {

    protected $format;

    function setValidation($min, $max) {
        $this->min = $min;
        $this->max = $max;
    }


    function map($val) {
        return $val;
    }

    function getTimestamp($date) {
        $d = \DateTime::createFromFormat('Y-m-d\TH:i', $date);
        if (!$d) {
            throw new \Exception("Datetime could not be created from date: " . $date);
        }
        return $d->getTimestamp();
    }


    function validate($value) {

        if (is_array($value)) {
            foreach($this->value as $key=>$val) {
                $rule = $this->validateSize($this->getTimestamp($val));
                if ($rule != ValidationRules::OK) {
                    return $rule;
                }
            }
        } else {
            $rule = $this->validateSize($this->getTimestamp($value));
            if ($rule != ValidationRules::OK) {
                return $rule;
            }
        }
       
        return ValidationRules::OK;
    }

    
    function toSchema() {
        $arr=parent::toSchema();
        $arr["type"] = "Time";
        $arr["format"] = $this->format;
        return $arr;
    }

}