<?php

namespace PressToJamCore;

class ResultsRow {

    protected $cells = [];
    protected $children = [];
    protected $primary;
    protected $parent;
    protected $history = [];
    protected $data_row;

    function __construct($data_row, $row) {

        $this->data_row = $data_row;

        foreach($data_row->response_fields as $slug=>$field) {
            $this->cells[$slug] = $field;
            $this->cells[$slug]->mapOutput(array_shift($row));
        }

        
        $this->primary = $data_row->primary;
        $this->parent = $data_row->parent;
    }


    function __get($name) {
        if(property_exists($this, $name)) return $this->$name;
        else if (isset($this->cells[$name])) return $this->cells[$name]->value;
    }


    function validate() {
        foreach($data_row->encrypted_filter_fields as $slug=>$field) {
            $hash = array_shift($row);
            if (!password_verify($field->value, $hash)) {
                //now compare the password part of this
                throw new \Exception("Incorrect username or password");
            }
        }
    }

    
    function addChildren($children) {
        $this->children = $children;
    }


    function addHistory($history) {
        $this->history[] = $history;
    }


    function export() {
        $args=[];
        foreach($this->cells as $slug=>$cell) {
            $args[$slug] = $cell->export();
        }


        foreach($this->children as $slug=>$rows) {
            $args[$slug] = [];
            foreach($rows as $row) {
                $args[$slug][] = $row->export();
            }
        }

        $args["__history"] = [];
        foreach($this->history as $hist) {
            $args["__history"][] = $hist->export();
        }
        return $args;
    }

    function getCells() {
        return $this->cells;
    }
    

    function getCell($slug) {
        return (isset($this->cells[$slug])) ? $this->cells[$slug] : null;
    }


    function getKey() {
        if ($this->key) {
            return $this->cells[$this->key];
        }
    }


    function getParentKey() {
        if ($this->parent_key) {
            return $this->cells[$this->parent_key];
        }
    }

    function foldIn(array $ar) {
        foreach($this->data_row->children as $slug=>$arr) {
            if (!isset($this->children[$slug])) $this->children[$slug] = $arr;
            else {
                foreach($arr as $id=>$map) {
                    if (isset($this->children[$slug][$id])) $this->children[$slug][$id]->foldIn($map);
                    else $this->children[$slug][$id] = $map;
                }
            }
        }
    }
}