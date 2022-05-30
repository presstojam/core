<?php

namespace PressToJamCore\Cells;

class StringCell extends MetaCell {

    protected $encrypted = false;
    protected $tests=[];

    function __construct() {
        parent::__construct();
        $this->default = "";
    }

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
        $value = trim($value);
        $this->validateSize(strlen($value));
        if ($this->last_error == ValidationRules::OK) {
            $this->validateValue($value);
        }
            
        if ($this->last_error == ValidationRules::OK) {
            return $value;
        } else {
            return null;
        }
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

    function getRandom($size, $salt = "", $num_only = false)
	{
		if ($num_only) $permitted_chars = "0123456789";
		else $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$code = $salt . substr(str_shuffle($permitted_chars), 0, $size);
		return $code;
	}

    function registerUniqueTest($test) {
        $this->tests[] = $test;
    }

}