<?php

namespace PressToJamCore;

use PressToJamCore\Cells as Cell;

class DObject {

    protected $meta=[];
    protected $fields=[];
    protected $aliases=[];
    protected $table;
    protected $structs=[];

    function __construct($table, ?string $name = "") {
        $this->table = $table;
        if (!$name) $name = $table;
        $this->meta["name"] = $name;
    }


    function __set($name, $value) {
        if (property_exists($this, $name)) $this->$name = $value;
        else if ($name[0] == "_") {
            $this->meta[substr($name, 1)] = $value;
        } else if (isset($this->fields[$name])) {
            $this->fields[$name]->value = $value;
            $this->fields[$name]->addState(Cell\CellStates::write);
        }
    }


    function __get($name) {
        if (property_exists($this, $name)) return $this->$name;
        else if ($name[0] == "_") {
            $name = substr($name, 1);
            return (isset($this->meta[$name])) ? $this->meta[$name] : null;
        } else if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }  
        else return null;
    }

    function addStruct($struct) {
        $this->structs[] = $struct;
    }

    
    function getPrimary() {
        return $this->fields[$this->meta["primary_key"]]->value;
    }


    function turnOn($fields=null) {
        if ($fields) {
            foreach($this->fields as $name=>$cell) {
                if (in_array($name, $fields)) $cell->addState(Cell\CellStates::read);
            }  
        } else {
            foreach ($this->fields as $name=>$cell) {
                $cell->addState(Cell\CellStates::read);
            }
        }      
    }

    function turnOnKeys() {
        $this->fields[$this->_primary_key]->addState(Cell\CellStates::read);
        if ($this->_parent_key) $this->fields[$this->_parent_key]->addState(Cell\CellStates::read);
    }

    
    function turnOff() {
        foreach($this->fields as $cell) {
            $cell->removeStates();
        }      
    }

    
    function validate() {
        $errors=[];
        foreach ($this->fields as $field=>$cell) {
            if ($cell->hasState(Cell\CellStates::filter) or $cell->hasState(Cell\CellStates::write)) {
                $rule = $cell->validate();
                if ($rule != ValidationRules::OK) {
                    $errors[$field] = $rule;
                }
            }
        }
        return $errors;
    }


    function schemaLimitFields($fields) {
        foreach($fields as $name) {
            if (isset($this->fields[$name])) {
                $this->fields[$name]->addState(Cell\CellStates::read);
            }
        }
    }
                    
    function schemaApplyFilters($request) {
        foreach($request as $field=>$val) {
            if (isset($this->fields[$field])) $this->fields[$field]->addState(Cell\CellStates::filter);
        }
    }

    function schemaApplyOrder($order) {
        foreach($order as $field=>$val) {
            $val = ($val == "asc" OR $val) ? Cell\CellStates::order_asc : Cell\CellStates::order_desc;
            if (isset($this->fields[$field])) $this->fields[$field]->addState($val);
        }
    }
        
        
    function schemaApplyGroup($group) {
        foreach ($group as $field) {
            if (isset($this->fields[$field])) {
                $this->fields[$field]->addState(Cell\CellStates::group);
            }
        }
    }

    
    function mapToStmtCols($pieces, $filter = null) {
        $table = $this->_name;
        if ($filter === null) $filter = Cell\CellStates::read;
        foreach ($this->fields as $field=>$cell) {
            if ($filter AND $cell->hasState($filter)) {
                $pieces->cols[] = $table . "." . $field;
            }
        }
    }


    function mapToOrderCols($pieces)
    {
        $table = $this->_name;
        foreach ($this->fields as $field=>$cell) {
            if ($cell->hasState(Cell\CellStates::order_asc)) {
                $pieces->order_cols[] = $table. "." . $field . " ASC";
            } elseif ($cell->hasState(Cell\CellStates::order_desc)) {
                $pieces->order_cols[] = $table. "." . $field . " DESC";
            }
        }
    }


    function mapToGroupCols($pieces)
    {
        $table = $this->_name;
        foreach ($this->fields as $field=>$cell) {
            if ($cell->hasState(Cell\CellStates::group)) {
                $pieces->group_cols[] = $table. "." . $field;
            } 
        }
    }


    function map($request)
    {
        //this maps a request array into the object, different to a thing.
        foreach ($request as $name=>$val) {
            $cell = $this->$name;
            if (!$cell) {
                throw new Exceptions\CellException($this->_name, $name);
            }

            if ($cell->hasState(Cell\CellStates::encrypted)) {
                continue;
            }
            if (!$cell->hasEitherStates([Cell\CellStates::filter, Cell\CellStates::write])) {
                throw new Exceptions\DataMapException($this->_name, $name, $val, $cell->states);
            }
            $cell->map($val);
        }
    }


 
    function getArgs($flatten = false) {
        $arr = ["filter"=>[], "write"=> []];
        foreach ($this->fields as $name=>$cell) {
            if ($cell->hasState(Cell\CellStates::write)) {
                $arr["write"][] = $cell->value;
            }
            if ($cell->hasState(Cell\CellStates::filter)) {
                $value = $cell->value;
                if (is_array($value)) $arr["filter"] = array_merge($arr["filter"], array_values($value));
                else $arr["filter"][] = $value;
            }
        }
        if ($flatten) $arr=array_merge($arr["write"], $arr["filter"]);
       return $arr;
    }


    function getArgsByRow($row) {
        $arr = ["filter"=>[], "write"=>[]];
        $getValue = function($cell) {
            if ($cell->getType != Cell\CellValueType::set) {
                throw new Error("Can't divide sets into rows for non set value " . $name);
            }
            $value = $cell->value;
            if (count($value) < $row) {
                throw new Error("Not enough values in set for " . $name);
            }
            return $value[$row];
        };

        foreach ($this->fields as $name=>$cell) {
            if ($cell->hasState(Cell\CellStates::write)) {
                $arr["write"][] = $getValue($cell);
            }
            if ($cell->hasState(Cell\CellStates::filter)) {
                $arr["filter"][] = $getValue($cell);
            }
        }
       return $arr;
    }



    function exportNode($node) {
        $primary = $node->getPrimary();
        $arr = $node->export();
        //will only need to run through references onces
        foreach($node->_refs as $ref=>$stmt) {
            if ($stmt->isOn()) {
                $data=[];
                $this->convertToData($data, $stmt);
                $data=array_values($data);
                if (count($data) > 0) $arr[$ref] = $data[0];
            }
        }
        return $arr;
    }


    function convertToSQLPieces() {
        $pieces = new StmtPieces();
        $pieces->from = $this->table;
        if ($this->_name != $this->table) $pieces->from .= " " . $this->_name;
        $this->mapToStmtCols($pieces);
        $this->mapToStmtFilters($pieces);
        return $pieces;
    }
}