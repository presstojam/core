<?php

namespace PressToJamCore;

class ResultsMap {

    protected $cells = [];
    protected $children = [];
    protected $key;
    protected $parent_key;
    protected $history = [];

    function __construct() {

    }

    function addChildren($children) {
        $this->children = $children;
    }


    function addHistory($history) {
        $this->history[] = $history;
    }


    function addCell($slug, $meta, $value = null) {
        $this->cells[$slug] = new Cells\DataCell($meta);
        $this->cells[$slug]->value = $value;
        if ($meta->is_primary) $this->key = $slug;
        else if ($meta->is_parent) $this->parent_key = $slug;
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
}