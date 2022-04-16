<?php

namespace PressToJamCore\Cells;

class StringCell extends MetaCell {

    protected $encrypted = false;

    function __get($name) {
        if (property_exists($this, $name)) return $this->$name;
        else return null;
    }
    

    function setType($data) {
        if (is_array($data)) $this->type = CellValueType::set;
        else $this->type = CellValueType::fixed;
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
        } else {
            $value = trim($value);
        }
        return $value;
    }


    function validate($value) {
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

        

        if (is_array($value)) {
            foreach ($value as $key=>$val) {
                $rule = $validateVal($this, $val);
                if ($rule != ValidationRules::OK) {
                    return $rule;
                }
            }
        } else {
            $rule = $validateVal($this, $value);
            if ($rule != ValidationRules::OK) {
                return $rule;
            }
        }
        return ValidationRules::OK;
    }


    function mapToStmtFilter($col) {
        if ($this->type == CellValueType::set) {
            return $col . " LIKE ?";
        } else {
            return $col . " = ?";
        }
    }

    function export($val) {
        if ($this->encrypted) return "xxxxxxxx";
        else return $val;
    }

    function toSchema() {
        $arr = parent::toSchema();
        $arr["type"] = "String";
        if ($this->encrypted) $arr["encrypted"] = true;
        return $arr;
    }

}