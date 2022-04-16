<?php

namespace PressToJamCore\Cells;

class FlagCell extends MetaCell {

    protected $is_primary = false;
    protected $is_parent = false;
    protected $reference = null;
    protected $circular = false;



    function setType($data) {
        if (is_array($data)) {
           $this->type = CellValueType::set;
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
                $value[$key] = ($val) ? 1 : 0;
            }
        } else {
            $value = ($value) ? 1 : 0;
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
        return $col .= " = ?";
    }


    function export($val) {
        return $val;
    }

    function toSchema() {
        $arr=parent::toSchema();
        $arr["type"] = "Flag";
        return $arr;
    }
}