<?php

namespace PressToJamCore;

class ResultsIndex  {

    protected $collections = [];
  

    function __construct($model = null, $res = null) {
        if ($model) {
            $this->collections[$model] = [];
            foreach ($res as $row) {
                $this->collections[$model][$row->{"--id"}] = $row;
            }
        }
    }


    function append($slug, $results, $parents) {
        if (!isset($this->collections[$slug])) $this->collections[$slug] = [];
        foreach($results as $row) {
            $this->collections[$slug][$row->{ "--id"}] = $row;
            foreach($parents as $parent=>$field) {
                if (isset($this->collections[$parent])) {
                    if (isset($this->collections[$parent][$row->$field])) {
                        $this->collections[$parent][$row->$field]->addChild($slug, $row);
                    }
                }
            }
        }
    }

    function getCollection($name) {
        return $this->collections[$name];
    }

    function get($slug, $id) {
        if (!isset($this->collections[$slug])) return null;
        return $this->collections[$slug][$id];
    }


}