<?php

namespace PressToJamCore;

class Request {

    private $data=[]; //data to be used in the where or having clause
    private $order = []; //key => value
    private $fields = []; //should end up as table => []
    private $group = []; //should end up as table => []
    private $limit;
    private $page = 0;
    private $raw;
    private $debug = false;
    private $to;
    private $to_secure;
    private $to_common;
    private $children=[];
    private $chunk;
    private $blob;
    private $history;
    protected $user_id = 0;

    function __construct($request = []) {
        if (isset($request['page'])) unset($request['page']);
        $this->data = [];
        foreach($request as $key=>$val) {
            if (strpos($key, "__") === 0 AND $key != "__key") continue;
            $this->data[$key] = $val;
        }
        if (isset($request['__order'])) $this->order = $this->convertKeys($request['__order']);
        if (isset($request['__group'])) $this->group = $this->convertKeys($request['__group']);
        if (isset($request['__fields'])) $this->fields = $this->convertKeys($request['__fields']);
        if (isset($request['__limit'])) $this->limit = $request['__limit'];
        if (isset($request['__page'])) $this->page = $request['__page'];
        if (isset($request['__raw'])) $this->raw = $request['__raw'];
        if (isset($request['__debug'])) $this->debug =true;
        if (isset($request['__to'])) $this->to = $request['__to'];
        if (isset($request['__children'])) $this->children = $request['__children'];
        if (isset($request['__chunk'])) $this->chunk = $request['__chunk'];
        if (isset($request['__blob'])) $this->blob = $request['__blob'];
        if (isset($request['__history'])) $this->history = $request['__history'];
    }

    function __get($name) {
        if (property_exists($this, $name)) return $this->$name;
        else return null;
    }

    function __set($name, $val) {
        if (property_exists($this, $name)) return $this->$name = $val;
    }

    function setID($id) {
        $this->user_id = $id;
    }

    function convertKeys($data) {
        $arr=[];
        foreach($data as $key=>$val) {
            if (strpos($key, "__") === 0) continue;
            if (strpos($key, "/") !== false) {
                $exp = explode("/", $key);
                if (!isset($arr[$exp[0]])) $arr[$exp[0]] = [];
                $arr[$exp[0]][$exp[1]] = $val;
            } else if (is_array($val)) {
                $arr[$key] = $this->convertKeys($val);
            } else {
                $arr[$key] = $val;
            }
        }
        return $arr;
    }

    function setModel($name, $fields) {
        if (!isset($this->data[$name])) $this->data[$name] = [];
        if (!isset($this->fields[$name])) $this->fields[$name] = [];
        if (!isset($this->order[$name])) $this->order[$name] = [];
        if (!isset($this->group[$name])) $this->group[$name] = [];

        foreach($fields as $field) {
            if (isset($this->data[$field])) {
                $this->data[$name][$field] = $this->data[$field];
                unset($this->data[$field]);
            }

            $pos = array_search($field, $this->fields);
            if ($pos !== false) {
                unset($this->fields[$pos]);
                $this->fields[$name][] = $field;
            }

            if (isset($this->order[$field])) {
                $this->order[$name][$field] = $this->order[$field];
                unset($this->order[$field]);
            }

            $pos = array_search($field, $this->group);
            if ($pos !== false) {
                unset($this->group[$pos]);
                $this->group[$name][] = $pos;
            }
        }

        //tidy up afterwards
        if (count($this->data[$name]) == 0) unset($this->data[$name]);
        if (count($this->fields[$name]) == 0) unset($this->fields[$name]);
        if (count($this->order[$name]) == 0) unset($this->order[$name]);
        if (count($this->group[$name]) == 0) unset($this->group[$name]);
 
    }
    

    function mapAliases($model, $aliases) {
        $mapKeys = function(&$arr, $aliases) {
            foreach($aliases as $key=>$val) {
                if (isset($arr[$key])) {
                    $arr[$val] = $arr[$key];
                    unset($arr[$key]);
                }
            }
        };

        $mapVals = function(&$arr, $aliases) {
            foreach($aliases as $key=>$val) {
                $pos = array_search($key, $arr);
                if ($pos !== false) {
                    $arr[$pos] = $val;
                }
            }
        };


        if (isset($this->data[$model])) $mapKeys($this->data[$model], $aliases);
        if (isset($this->fields[$model])) $mapVals($this->fields[$model], $aliases);
        if (isset($this->order[$model])) $mapKeys($this->order[$model], $aliases);
        if (isset($this->group[$model])) $mapVals($this->group[$model], $aliases);
    }

    function addValue($name, $key, $model = null) {
        if($model) {
            if (!isset($this->data[$model])) $this->data[$model] = [];
            $this->data[$model][$name] = $key;
        } else {
            $this->data[$name] = $key;
        }
    }

    function removeValue($name, $model = null) {
        if ($model) {
            if (isset($this->data[$model])) {
                if (isset($this->data[$model][$name])) {
                    unset($this->data[$model][$name]);
                    if (count($this->data[$model]) == 0) {
                        unset($this->data[$model]);
                    }
                }
            }
        } else {
            if (isset($this->data[$name])) unset($this->data[$name]);
        }
    }
    

    function mapUser($table, $col) {
        if(!isset($this->data[$table])) $this->data[$table] = [];
        $this->data[$table][$col] = $this->user_id;
    }
}