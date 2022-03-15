<?php

namespace PressToJamCore;

class DataNode {

    function loadParent($to) {
        if ($to == $this->_meta["name"] OR !$this->parent) return;
        $this->parent->on();
        $this->parent->turnOn();
    }


    function loadChildren($children) {
        $keys=array_keys($children);
        $has_child = false;
        foreach($children as $child) {
            if (in_array($child, $keys)) {
                $child->on();
                $child->turnOn();
                $has_child = true;
            } 
            
            if ($child->loadChildren($children)) {
                //reset variables to ensure we catch children without any fields
                $child->on();
                $has_child = true;
            }
        }  
        return $has_child;
    }


    function loadReferences($refs = null) {
        foreach($this->_refs as $name=>$stmt) {
            if (!$refs OR !isset($refs[$name])) {
                $stmt->on();
                $stmt->turnOn();
            }
        }
    }


    function mapAll($request) {
        //need to work out where abouts the connections are to make the links
        
        if ($this->_parent) {
            if ($this->_parent->mapAll($request)) {
                $this->on();
            }
        }

        foreach($this->_children as $child=>$stmt) {
            if ($stmt->mapAll($request)) {
                $this->on();
            }
        }

        foreach($this->_refs as $stmt) {
            if ($stmt->mapAll($request)) {
                $this->on();
            }
        }

        if (isset($request[$this->_meta["name"]])) {
            $this->on();
            $this->map($request);
        }

        return $this->isOn();
    }


    function mapToAllStmtCols($pieces)
    {
        foreach (self::$_cache as $stmt) {
            if ($stmt->isOn()) {
                $stmt->mapToStmtCols($pieces);
            }
        }
    }

    function mapToAllFilterCols($pieces)
    {
        foreach (self::$_cache as $stmt) {
            if ($stmt->isOn()) {
                $stmt->mapFilterCols($pieces);
            }
        }
    }


    function on() {
        $this->_on = true;
    }

    function off() { 
        $this->_on = true;
    }

    function isOn() {
        return $this->_on;
    }
}