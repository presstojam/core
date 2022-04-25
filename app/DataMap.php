<?php

namespace PressToJamCore;

class DataMap {

    protected $cells = [];

    function __construct() {

    }

    function __get($slug) {
        if (isset($cells[$slug])) return $cells[$slug];
    }


    function addCell($slug, $meta, $value = null) {
        $this->cells[$slug] = new Cells\DataCell($meta);
        $this->cells[$slug]->value = $value;
        $this->cells[$slug]->setType();
    }


    function setKey($id) {
        foreach($this->cells as $slug=>$cell) {
            if ($cell->meta_field->is_primary OR $cell->meta_field->is_parent) {
                $cell->value = $id;
                $cell->setType();
            }        
        }
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
            $val = $cell->toArg();
            if (is_array($val)) $args=array_merge($args, $val);
            else $args[] = $val;
        }
        return $args;
    }


    function export() {
        $args=[];
        foreach($this->cells as $slug=>$cell) {
            $args[$slug] = $cell->export();
        }
        return $args;
    }


    function calculateAssets() {
        foreach($this->cells as $slug=>$cell) {
            if (get_class($cell) == "PressToJamCore\Cells\AssetCell") {
                $cell->calculate($this);
            }
        }
    }

    function getCell($slug) {
        return (isset($this->cells[$slug])) ? $this->cells[$slug] : null;
    }

    
}