<?php

namespace PressToJamCore;

class ResultsMap {

    protected $cells = [];

    function __construct() {

    }


    function addCell($slug, $meta, $value = null) {
        $this->cells[$slug] = new Cells\DataCell($meta);
        $this->cells[$slug]->value = $value;
    }

    function export() {
        $args=[];
        foreach($this->cells as $slug=>$cell) {
            $args[$slug] = $cell->export();
        }
        return $args;
    }
}