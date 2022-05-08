<?php

namespace PressToJamCore;

class DataRow {

    protected $alias = "";
    protected $table = "";
    protected $joins = [];
    protected $children = [];
    protected $filter_fields = [];
    protected $encrypted_filter_fields = [];
    protected $data_fields = [];
    protected $response_fields = [];
    protected $limit = "";
    protected $group = [];
    protected $sort = [];
    protected $primary = null;
    protected $parent = null;
    protected $to;
    protected $copy_table=null;
   
    
    function __construct() {
    }


    function __get($key) {
        if ($key == "from") return $this->table . " " . $this->alias;
        else if (property_exists($this, $key)) return $this->$key;
    }

    function __set($key, $val) {
        if (property_exists($this, $key)) $this->$key = $val;
    }

    function addField($alias, $slug, $field) {
        $this->data_fields[$slug] = new Cells\DataCell($field);
        $this->data_fields[$slug]->alias = $alias;
        if ($field->is_primary) $this->primary = $field;
        else if ($field->is_parent) $this->parent = $field;
    }

    function addResponse($alias, $slug, $field) {
        $this->response_fields[$slug] = new Cells\DataCell($field);
        $this->response_fields[$slug]->alias = $alias;
        if ($field->is_primary) $this->primary = $field;
        else if ($field->is_parent) $this->parent = $field;
    }


    function addFilter($alias, $slug, $field) {
        $this->filter_fields[$slug] = new Cells\DataCell($field);
        $this->filter_fields[$slug]->alias = $alias;
    }


    function addEncryptedFilter($alias, $slug, $field) {
        $this->encrypted_filter_fields[$slug] = new Cells\DataCell($field);
        $this->encrypted_filter_fields[$slug]->alias = $alias;
    }


    function addJoin($slug, $join) {
        $this->joins[$slug] = $join;
    }

    function addFrom($alias, $table) {
        $this->table = $table;
        $this->alias = $alias;
    }


    function mapRequestMeta(Params $params) {
        $this->sort = $params->sort;
        $this->group = $params->group;
        $this->limit = $params->limit;
    }


    function mapRequestData(Params $params) {
        foreach($this->data_fields as $slug=>$field) {
            if (isset($params->data[$slug])) $field->map($params->data[$slug]);
        }

        foreach($this->filter_fields as $slug=>$field) {
            if (isset($params->data[$slug])) {
                $field->map($params->data[$slug]);
            }
        }

        foreach($this->encrypted_filter_fields as $slug=>$field) {
            if (isset($params->data[$slug])) {
                $field->map($params->data[$slug]);
            }
        }
    }


    function validateFieldsIn() {
        foreach($this->data_fields as $field) {
            $field->validate();
        }

        foreach($this->filter_fields as $field) {
            $field->validate();
        }
    }


    function validateRow() {

    }



    function toArgs() {
        $args=[];
        foreach($this->data_fields as $field) {
            $args[] = $field->toArg();
        }

        foreach($this->filter_fields as $field) {
            $args[] = $field->toArg();
        }
        return $args;
    }
}