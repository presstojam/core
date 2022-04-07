<?php

namespace PressToJamCore;

use PressToJamCore\Cells as Cell;
use PressToJam\DataObjects as Data;

class DObjectRels {

    protected $cache = [];
    protected $structs = [];
  
    function __construct($dobject) {
        $this->cache[$dobject->_name] = $dobject;
    }

    function addObject($dobject) {
        $this->cache[$dobject->_name] = $dobject;
    }

    function convertToClassCase($name) {
        $parts = explode("-", $name);
        $cname = "";
        foreach ($parts as $part) {
            $cname .= ucfirst(strtolower($part));
        }
        return $cname;
    }


    function getRoot() {
        return (count($this->cache) > 0) ? $this->cache[array_key_first($this->cache)] : null;
    }

    function getStmt($name) {
        return (isset($this->cache[$name])) ? $this->cache[$name] : null;
    }

    function init(Request $request)
    {
        $dobject = $this->cache[array_key_first($this->cache)];
        $this->initReferences($dobject);
        if ($request->to) {
            $this->initParent($dobject, $request->to, true);
        }
        if ($request->to_common) {
            $this->initParent($dobject, $request->to_common);
        }
        if ($request->to_secure) {
            $this->initParent($dobject, $request->to_secure);
        }

        if (count($request->children) > 0) $this->initChildren($dobject, $request->children);
    }



    function initParent($node, $to, $turn_on = false) {
        $parent = $node->getParent();
        if (!$parent) return;

        if(!isset($this->cache[$parent->to])) {
            $ns_name = "\PressToJam\DataObjects\\" . $parent->class_name;
            $this->cache[$parent->to] = new $ns_name();
        }

        if ($turn_on) $this->cache[$parent->to]->turnOn();
        else $this->cache[$parent->to]->turnOnKeys();
        $this->structs[] = $parent;
      
        if ($parent->to == $to) return;

        $this->initParent($this->cache[$parent->to], $to);
    }


    function initChildren($node, $children) {
        $all_children = $node->getChildren();
        foreach($all_children as $child=>$struct) {
            if (in_array($child, $children)) {
                if (!isset($this->cache[$struct->to])) {
                    $ns_name = "\PressToJam\DataObjects\\" . $struct->class_name;
                    $this->cache[$struct->to] = new $ns_name();
                }
                $this->cache[$struct->to]->turnOn();
                $this->initReferences($this->cache[$struct->to]);
                $this->structs[$struct->to] = $struct;
                $this->initChildren($this->cache[$struct->to], $children);
            }
        }
    }


    function initAllChildren($node = false) {
        if (!$node) $node = $this->getRoot();
        $all_children = $node->getChildren();
        foreach($all_children as $child=>$struct) {
            if (!isset($this->cache[$struct->to])) {
                $ns_name = "\PressToJam\DataObjects\\" . $struct->class_name;
                $this->cache[$struct->to] = new $ns_name();
            }
            $this->cache[$struct->to]->turnOn();
            $this->structs[] = $struct;
            $this->initAllChildren($this->cache[$struct->to]);
        }
    }

    function initReferences($node, $limitrefs = []) {
        if (in_array($node->_name, $limitrefs)) return;
        $refs = $node->getReferences();
        foreach($refs as $ref) {
            if (!isset($this->cache[$ref->to])) {
                $ns_name = "\PressToJam\DataObjects\\" . $ref->class_name;
                $ref->alias = "t" . count($this->structs);
                $this->cache[$ref->alias] = new $ns_name($ref->alias);
                $this->cache[$ref->alias]->turnOnSummary();
                $this->structs[] = $ref;
            }
        }
    }


    function __set($name, $value) {
        if (property_exists($this, $name)) $this->$name = $value;
    }


    function __get($name) {
        if (property_exists($this, $name)) return $this->$name;
        else return null;
    }


    function turnOn() {
        foreach($this->cache as $stmt) {
            $stmt->turnOn();
        }
    }

    function mapAliases($request) {
        foreach($this->cache as $name=>$stmt) {
            $request->mapAliases($name, $stmt->aliases);
        }
    }


    function mapAll($request) {
        foreach ($this->cache as $stmt) {
            if (isset($request[$stmt->_name])) {
                $stmt->map($request[$stmt->_name]);
            }
        }
    }



    function getArgs() {
        $args=["filter"=>[],"write"=>[]];
        foreach($this->cache as $stmt) {
            $cargs = $stmt->getArgs();
            $args["filter"] = array_merge($args["filter"], $cargs["filter"]);
            $args["write"] = array_merge($args["write"], $cargs["write"]);
        }
        $args = array_merge($args["write"], $args["filter"]);
        return $args;
    }


    function mapAllOutputs($row) {
        foreach ($this->cache as $stmt) {
            $stmt->mapOutput($row);
        }
    }
  


    function export($tree, $raw) {

        foreach($this->cache as $name=>$stmt) {
            $arr = $stmt->export($raw);
            $id = $stmt->getPrimary();
            if ($id) $tree->addData($name, $id, $arr);
        }
    }

    function getAllChildren($node = null) {
        if (!$node) $node = $this;
        $arr=[];
        $children = $node->getChildren();
        foreach($children as $child) {
            $stmt = $this->cache[$child->to];
            $arr[] = $stmt;
            $arr = array_merge($arr, $this->getAllChildren($stmt));
        }
        return $arr;
    }

    function getAllParents() {
        $parents=[];
        $node = $this->cache[array_key_first($this->cache)];
        $struct = $node->getParent();
        while($struct) {
            $node = $this->cache[$struct->to];
            $parents[] = $node;
            $struct = $node->getParent();
        }
        return $parents;
    }



    function buildJoin($stmtpieces) {
        foreach($this->structs as $struct) {
            $stmtpieces->join .= ($struct->required) ? " INNER JOIN " : " LEFT OUTER JOIN ";
            $stmtpieces->join .= $struct->to;
            if ($struct->alias) $stmtpieces->join .= " " . $struct->alias;
            $stmtpieces->join .= " ON ";
            $stmtpieces->join .= $struct->from . "." . $struct->from_col;
            $to = ($struct->alias) ? $struct->alias : $struct->to;
            $stmtpieces->join .= " = " . $to . "." . $struct->to_col;
        }
    }



    function schemaApplyParents($to, $filter_only = false) {
        $parents = $this->getAllParents();
        foreach($parents as $parent) {
            if (!$filter_only) $parent->turnOn();
            if ($parent->_name == $to) break;
        }
    }


    function schemaApplyChildren($children, $tree = null) {
        $childrens = $this->getAllChildren();
        foreach($childrens as $name=>$stmt) {
            if (in_array($name, $children)) {
                $stmt->turnOn();
            }
        }
    }


    function applySchemaRequest($request) {
        //order is important, must do fields first as we will remove all states
        if (count($request->fields) > 0) {
            foreach($this->cache as $name=>$stmt) {
                $stmt->turnOff();
                $stmt->turnOnKeys();
                if (isset($request->fields[$name])) {
                    $stmt->schemaLimitFields($request->fields[$name]);
                }
            }
        }
        
        foreach($request->data as $table=>$arr) {
            $stmt = $this->getStmt($table);
            if (!$stmt) continue;
            $stmt->schemaApplyFilters($arr);
        }

        
        foreach($request->fields as $table=>$arr) {
            $stmt = $this->getStmt($table);
            if (!$stmt) continue;
            $stmt->schemaLimitFields($arr);
        }

        foreach($request->order as $table=>$arr) {
            $stmt = $this->getStmt($table);
            if (!$stmt) continue;
            $stmt->schemaApplyOrder($arr);
        }

        foreach($request->group as $table=>$arr) {
            $stmt = $this->getStmt($table);
            if (!$stmt) continue;
            $stmt->schemaApplyGroup($arr);
        }
    }


    function applyData($request) {
        foreach($this->cache as $table=>$dobject) {
            $dobject->setDefaults();
            if (isset($request[$table])) {
                $dobject->map($request[$table]);
            }
        }
    }


    function validate() {
        $errors = [];
        foreach($this->cache as $table=>$dobject) {
            $errs = $dobject->validate();
            $errors = array_merge($errors, $errs);
        }
        return $errors;
    }

    function convertToSQLPieces($filter = Cell\CellStates::read) {
        $pieces = new StmtPieces();
        $pieces->from = $this->cache[array_key_first($this->cache)]->_name;

        foreach ($this->cache as $stmt) {
            $stmt->mapToStmtCols($pieces, $filter);
        }

        foreach ($this->cache as $stmt) {
            $stmt->mapToStmtFilters($pieces);
        }

        foreach ($this->cache as $stmt) {
            $stmt->mapToOrderCols($pieces);
        }

        foreach ($this->cache as $stmt) {
            $stmt->mapToGroupCols($pieces);
        }

        $this->buildJoin($pieces);
        return $pieces;
    }


    function calculate() {
        foreach ($this->cache as $stmt) {
            $stmt->calculate();
        }
    }
        
       
    function calculateAssets() {
        foreach ($this->cache as $stmt) {
            $stmt->calculateAssets();
        }
    }
        
        
    function removeAssets() {
        foreach ($this->cache as $stmt) {
            $stmt->removeAssets();
        }       
    }

    function getAllStructs() {
        $structs = $this->structs;
        foreach($this->cache as $stmt) {
            $structs = array_merge($structs, $stmt->getVirtualStructs());
        }
        return $structs;
    }

    function getActiveArchives() {
        $arr=[];
        foreach($this->cache as $stmt) {
            if ($stmt->_has_archive) {
                $arr[] = $stmt->getArchiveName();
            }
        }
        return $arr;
    }

}