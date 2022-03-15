<?php
namespace PressToJamCore\Responses;

class Data {
    private $data=[];
    private $children=[];
    private $refs=[];
    private $history = [];
    private $parent;
    private $parent_name;
    
    function __construct($data) {
        $this->data = $data;
    }

    function __get($key) {
        if (isset($this->data[$key])) return $this->data[$key];
        else if (property_exists($this, $key)) return $this->$key;
        else return null;
    }

    function getData($key) {
        if (isset($this->data[$key])) return $this->data[$key];
        else return null;
    }

    function addHistory($history) {
        $this->history = $history;
    }

    function addData($key, $value) {
        $this->data[$key] = $value;
    }

    function addParent($name, $obj) {
        $this->parent_name = $name;
        $this->parent = $obj;
    }

    function addChild($name, $key, $obj) {
        if (!isset($this->children[$name])) $this->children[$name] = [];
        $this->children[$name][$key] = $obj; 
    }

    function addReference($name, $obj) {
        $this->refs[$name] = $obj;
    }

    function convertToArr() {
        $arr = $this->data;
        $arr['_history'] = $this->history;
        foreach($this->refs as $name=>$data) {
            $arr[$name] = $data->convertToArr();
        }

        $parent = $this->parent;
        $parent_name = $this->parent_name;
        while($parent) {
            $arr[$parent_name] = $parent->data;
            $parent_name = $parent->parent_name;
            $parent = $parent->parent;
        }

        foreach($this->children as $name=>$child_arr) {
            $arr[$name] = [];
            foreach($child_arr as $id=>$obj) {
                $arr[$name][] = $obj->convertToArr();
            }
        }
        return $arr;
    }

}

class DataTree  {

    private $root;
    private $data_models=[];
    private $rels;


    function addData($name, $id, $data) {
        if (!isset($this->data_models[$name])) $this->data_models[$name] = [];
        $this->data_models[$name][$id] = new Data($data);
    }

    function getDataObj($name, $id) {
        if (!isset($this->data_models[$name])) return null;
        else if (!isset($this->data_models[$name][$id])) return null;
        else return $this->data_models[$name][$id];
    }

    function getData($name) {
        if (!isset($this->data_models[$name])) return [];
        else return $this->data_models[$name];
    }


    function getDataObjWhere($name, $col, $id) {
        if (!isset($this->data_models[$name])) return null;
        foreach($this->data_models[$name] as $obj) {
            if ($obj->getData($col) == $id) return $obj;
        }
        return null;
    }

    
    function setRels($structs) {
       // var_dump($this->data_models);
        if (count($this->data_models) == 0) return;
        foreach($structs as $struct) {
            if (!isset($this->data_models[$struct->to])) {
                if ($struct->required) {
                    throw new \Exception("Missing required tree model for " . $struct->to);
                }
                continue;
            }
            if ($struct->type == \PressToJamCore\DObjectStructTypes::child) {
                foreach($this->data_models[$struct->to] as $child_id=>$child_obj) {
                    $obj = $this->getDataObj($struct->from, $child_obj->getData($struct->to_col));
                    if (!$obj) {
                        throw new \Exception("Trying to connect child " . $struct->to_col . " to missing tree " . $struct->from);
                    } 
                    $obj->addChild($struct->display_name, $child_id, $child_obj);
                }
            } else if ($struct->type == \PressToJamCore\DObjectStructTypes::ref) {
                foreach($this->data_models[$struct->to] as $ref_obj) {
                    $obj = $this->getDataObjWhere($struct->from, $struct->from_col, $ref_obj->getData($struct->to_col));
                    if (!$obj) {
                        throw new \Exception("Trying to connect reference " . $struct->to_col . " to missing tree " . $struct->from);
                    }
                    $obj->addReference($struct->display_name, $ref_obj);
                }
            } else if ($struct->type == \PressToJamCore\DObjectStructTypes::circular) {
                $rem = array();
                foreach($this->data_models[$struct->to] as $child_id=>$child_obj) {
                    $circular_id = $child_obj->getData($struct->to_col);
                    if ($circular_id) {
                        $obj = $this->getDataObj($struct->from, $child_obj->getData($struct->to_col));
                        if ($obj) {
                            $obj->addChild($struct->display_name, $child_id, $child_obj);
                        $rem[] = $child_id;
                        }
                        
                    }
                }
                foreach($rem as $key) {
                    unset($this->data_models[$struct->to][$key]);
                }
            } else {
                foreach($this->data_models[$struct->to] as $parent_obj) {
                    $obj = $this->getDataObjWhere($struct->from, $struct->from_col, $parent_obj->getData($struct->to_col));
                    if (!$obj) {
                        throw new \Exception("Trying to connect parent " . $struct->to_col . " to missing tree " . $struct->from);
                    }
                    $obj->addParent($struct->display_name, $parent_obj);
                }
            }
        }
    }


    //make a structure class that can then be used instead of the metas in dobject
    function convertToSingletonArr() {
        $farr = [];
        if (count($this->data_models) == 0) return $farr;
        $root = $this->data_models[array_key_first($this->data_models)];
        $root_node = array_shift($root);
        return $root_node->convertToArr();
    }   

    function convertToArr() {
        $farr = [];
        if (count($this->data_models) == 0) return $farr;
        $root = $this->data_models[array_key_first($this->data_models)];
        foreach($root as $key=>$row) {
            $farr[] = $row->convertToArr();
        }
        return $farr;
    }

}