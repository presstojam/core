<?php

namespace PressToJamCore\Cells;

class NumberCell extends MetaCell {

    protected $round = 0;

    function __construct() {
        parent::__construct();
        $this->default = 0;
    }

    function __get($name) {
        if (property_exists($this, $name)) return $this->$name;
        else return null;
    }


    function setType($data) {
        if (is_array($data)) {
            if (isset($data['min']) AND isset($data['max'])) $this->type = CellValueType::range;
            else if (isset($data['min'])) $this->type = CellValueType::min;
            else if (isset($data['max'])) $this->type = CellValueType::max;
            else $this->type = CellValueType::set;
        } else {
            $this->type = CellValueType::fixed;
        }
    }
    

    function setValidation($min, $max) {
        $this->min = $min;
        $this->max = $max;
    }

    function mapOutput($val) {
        return (int) $val;
    }

    function map($value) {
        if (is_array($value)) {
            foreach($value as $key=>$val) {
                $value[$key] = (is_numeric($val)) ? $val : 0;
            }
        } else {
            $value = (is_numeric($value)) ? $value : 0;
        }
        return $value;
    }


    function validate($value) {        
        if (is_array($value)) {
            foreach ($value as $val) {
                $rule = $this->validateSize($val);
                if ($rule != ValidationRules::OK) {
                    return $rule;
                }
            }
        } else {         
            $rule = $this->validateSize($value);         
            if ($rule != ValidationRules::OK) {
                return $rule;
            }
        }

        return ValidationRules::OK;
    }


    function mapToStmtFilter($col) {
        if ($this->type == CellValueType::range) {
            return $col . " >= ? AND " . $col . " <= ?";
        } else if ($this->type == CellValueType::min) {
            return $col . " >= ?";
        } else if ($this->type == CellValueType::max) {
            return $col . " <= ?";
        } else {
            return $col .= " = ?";
        }
    }


    function toSchema() {
        $arr = parent::toSchema();
        $arr["type"] = "Number";
        $arr["round"] = $this->round;
        return $arr;
    }
}