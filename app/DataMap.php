<?php

namespace PressToJamCore;

class DataMap {

    protected $cells = [];

    function __construct() {

    }


    function addCell($meta, $value = null) {
        $this->cells[$meta->name] = new Cells\DataCell($meta);
        $this->cells[$meta->name]->value = $value;
    }


    function validate() {
        $errors = [];
        foreach($this->cells as $cell) {
            $err = $cell->validate();
            if ($err != Cells\ValidationRules::OK) $errors[$cell->meta_field->name] = $err;
        }
       
        if (count($errors) > 0) {
            throw new Exceptions\ValidationException($errors);
        }
    }

    function toArgs() {
        $args=[];
        foreach($this->cells as $cell) {
            $val = $cell->value;
            if (is_array($val)) $args=array_merge($args, $val);
            else $args[] = $val;
        }
        return $args;
    }
}