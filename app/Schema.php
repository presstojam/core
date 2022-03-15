<?php

namespace PressToJamCore;

use PressToJamCore\Cells as Cell;

class Schema {

    protected $dobject;

    function __construct($dobject) {
        $this->dobject = $dobject;
        $this->dobject->_schema = $this;
        $this->dobject->reset(); //always reset when creating a new schema
    }


    function applyFields($fields) {
        $this->limitFields($fields);
        foreach($fields as $name=>$arr) {   
            if (is_array($arr)) {
                $stmt = $this->getStmt($name);
                $schema = $stmt->_schema;
                $schema->limitSchemaFields($arr);
            } 
        }
    }



    function applyFilters($request) {
        foreach ($request as $table=>$arr) {
            if (substr($table, 0, 2) == "__") continue;
            $stmt = $this->dobject->getStmt(str_replace("-", "_", $table));
            $schema = $stmt->_schema;
            $schema->applySchemaFilters($arr);
            $stmt->on();
        }
    }

    function applyParents($to) {
        $parents = $this->dobject->getAllParents();
        foreach($parents as $parent) {
            $parent->turnOn();
            $parent->on();
            if ($parent->_name == $to) break;
        }
    }


    function applyChildren($children, $tree = null) {
        $childrens = $this->dobject->getAllChildren();
        foreach($childrens as $name=>$stmt) {
            if (in_array(str_replace("_", "-", $name), $children)) {
                $stmt->turnOn();
                $stmt->on();

                //need to follow the process back up
                $parents = $stmt->getAllParents();
                foreach($parents as $parent) {
                    if (!$parent->isOn()) {
                        $parent->on();
                    } else {
                        break; //don't go any further up the tree as already on
                    }
                }
            }
        }
    }


    function applyOrder($order) {
        foreach($order as $key=>$val) {
            if (is_array($val)) {
                $stmt = $this->dobject->getStmt(str_replace("-", "_", $key));
                $stmt->on();
                $schema = $stmt->_schema;
                $schema->applySchemaOrder($val);
            }
         }
         $stmt=$this->dobject->_schema;
         $stmt->on();
         $schema->applySchemaOrder($order);
    }

    function limitReferences($refs = null) {
        $cache = $this->getActiveStmts();
        foreach($cache as $stmt) {
            foreach($stmt->refs as $name=>$stmt) {
                if (isset($refs[$stmt->_name])) {
                    $stmt->off();
                    $stmt->turnOff();
                }
            }
        }
    }


    function applyMeta($request) {
        if (isset($request["__to"])) {
            $this->applyParents($request["__to"]);
        } 

        if (isset($request["__children"])) {
            $this->applyChildren($request["__children"]);
        }

        if (isset($request["__fields"])) {
            $this->applyFields($request["__fields"]);
        }

        if (isset($request["__order"])) {
            $this->applyOrder($request["__order"]);
        }
    }

    
   
}