<?php

namespace PressToJamCore;

class MetaCollection {

    protected $table;
    protected $slug;
    protected $alias;
    protected $parent = null;
    protected $children = [];
    protected $references = [];
    protected $filter_fields = [];
    protected $data_fields = [];
    protected $map_fields = [];
    protected $required = false;
    protected $limit;
    protected $group;
    protected $sort = [];
    
    static protected $num = 1;
    
    function __construct() {
        $this->alias = "t" . self::$num;
        ++self::$num;
    }


    function __get($key) {
        if (property_exists($this, $key)) return $this->$key;
    }

    function __set($key, $val) {
        if (property_exists($this, $key)) $this->$key = $val;
    }

    function convertToDataMap($datamap, $filtermap) {
        foreach($this->data_fields as $slug=>$field) {
            $map->addCell($slug, $field);
        }

        foreach($this->filter_fields as $slug=>$field) {
            $filtermap->addCell($slug, $field);
        }

        if ($this->parent) $this->parent->convertToDataMap($datamap, $filtermap);

        foreach($this->references as $ref) {
            $ref->convertToDataMap($datamap, $filtermap);
        }

        foreach($this->children as $child) {
            $child->convertToDataMap($datamap, $filtermap);
        }
    }


    function getAllReferences() {
        $arr = [];
        foreach($this->references as $ref) {
            $arr[] = $ref;
            $arr=array_merge($arr, $ref->getAllReferences());
        }
        return $arr;
    }


    function getAllInputCollections() {
        $arr = [$this];
        $arr=array_merge($arr, $this->getAllReferences());
        if ($this->parent) {
            $arr=array_merge($arr, $this->parent->getAllInputCollections());
        }
        return $arr;
    }

    
    function getAllOutputCollections() {
        $arr=[];
        foreach($this->children as $child) {
            $arr[] = $child;
            $arr=array_merge($arr, $child->getAllReferences());
            $arr=array_merge($arr, $child->getAllChildren());
        }
        return $arr;
    }


    function buildGetFromRequest($request) {
        if (count($request->fields) > 0) {
            $this->activateSelectedFields($request->fields);
        } else {
            $this->activate();
        }

        if ($request->to) $this->addTo($request->to, true, $request->fields);

        if (count($request->children) > 0) {
            $this->addChildren($request->children, $request->fields);
        }

        $this->addReferences($request->fields);

        $collections = $this->getAllInputCollections();
        foreach($collections as $col) {
            $slug = $col->slug;
            $cdata = $request->data;
            $order = $request->sort;
            if ($slug) {
                if (!isset($cdata[$slug])) continue;
                $cdata = $cdata[$slug];
            }
            $col->activateFilterFields($cdata);
        }


        foreach($collections as $col) {
            $slug = $col->slug;
            $order = $request->sort;
            if ($slug) {
                if (!isset($order[$slug])) continue;
                $order = $order[$slug];
            }
            $col->activateSortFields($order);
        }

        $this->limit = $request->limit;
        if ($request->sort) $this->sort = $request->sort;
    }



    function fold($row, $map) {
        $collections = $this->getAllInputCollections();
        foreach ($collections as $col) {
            $alias = $col->alias;
            $fields = $col->data_fields;
            $slug = $col->slug;
            if ($slug) $slug .= "-";
        
            foreach ($fields as $fslug=>$field) {
                $map->addCell($slug . $fslug, $field, array_shift($row));
            }
        }
    }


    function getAsSchema() {
        $arr=[];
        foreach($this->data_fields as $slug=>$field) {
            $arr[$slug] = $field->toSchema();
        }

        foreach($this->references as $ref) {
            $arr[$ref->slug] = $ref->getAsSchema();
        }

        if ($this->parent) {
            $arr[$this->parent->slug] = $this->parent->getAsSchema();
        }
        return $arr;
        
    }
}