<?php

namespace PressToJamCore;

class DataShape {

    protected $fields = [];
    protected $filter_fields = [];
    protected $relationship_fields = [];
   
    function __construct() {
   
    }


    function __get($key) {
        if ($key == "from") return $this->table . " " . $this->alias;
        else if (property_exists($this, $key)) return $this->$key;
    }

    function __set($key, $val) {
        if (property_exists($this, $key)) $this->$key = $val;
    }

    function addField($slug, $field) {
        if (!isset($this->fields[$slug])) $this->fields[$slug] =$field;
    }

    function addFilter($slug, $field) {
        if (!isset($this->filter_fields[$slug])) $this->filter_fields[$slug] = $field;
    }

    function addRelationship($slug, $field) {
        if (!isset($this->relationship_fields[$slug])) $this->relationship_fields[$slug] = $field;
    }


    function map($data) {
        $errors = [];
        foreach($this->fields as $slug=>$field) {
            if (isset($data[$slug])) {
                $res = $field->map($data[$slug]);
                if ($res != Cells\ValidationRules::OK) {
                    $errors[$slug] = $res;
                }
            }
        }

        foreach($this->filter_fields as $slug=>$field) {
            if (isset($data[$slug])) {
                $res = $field->map($data[$slug]);
                if ($res != Cells\ValidationRules::OK) {
                    $errors[$slug] = $res;
                }
            }
        }

        if (count($errors) > 0) {
            throw Core\Exceptions\ValidationException($errors);
        }
    }


    function toArgs() {
        $args=[];
        foreach($this->fields as $field) {
            $args[] = $field->toArg();
        }

        foreach($this->filter_fields as $field) {
            $args[] = $field->toArg();
        }
        return $args;
    }


    function asSchema() {
        $res=["fields"=>[]];
        foreach($this->fields as $slug=>$field) {
            $res["fields"][$slug] = $field->toSchema();
        }
        return $res;
    }

}