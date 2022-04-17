<?php

namespace PressToJamCore\Cells;

class IdCell extends MetaCell {

    protected $is_primary = false;
    protected $is_parent = false;
    protected $reference = null;
    protected $is_circular = false;
    

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


    function export($val) {
        return $val;
    }

    function toSchema() {
        $arr=parent::toSchema();
        $arr["type"] = "ID";
        if ($this->is_primary) {
            $arr["is_primary"] = true;
        }
        if ($this->is_parent) {
            $arr["is_parent"] = true;
        }
        if ($this->reference) {
            $arr["reference"] = $this->reference;
        }
        if ($this->is_circular) {
            $arr["circular"] = true;
        }
        return $arr;
    }
}