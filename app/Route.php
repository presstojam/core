<?php

namespace PressToJamCore;

class RouteState {

    protected $method;
    protected $label;
    protected $title;
    protected $actions;
    protected $fields;
    protected $request;
    protected $dictionary;
    protected $meta;
   

    function __set($name, $val) {
        if (property_exists($this, $name)) $this->$name = $val;
    }


    function __get($name) {
        if (property_exists($this, $name)) return $this->$name;
    }

    function addField($name) {
        $this->fields[$name] = [];
    }

    function applyDictionary($chapter) {
        if ($chapter->label) $this->label = $chapter->label;
        if ($chapter->title) $this->title = $chapter->title;
        if ($chapter->actions) $this->actions = $chapter->actions;
    }

    function toArr() {
        return [
            "method"=>$this->method,
            "label"=>$this->label,
            "title"=>$this->title,
            "actions"=>$this->actions,
            "fields"=>$this->fields
        ];
    }
   
}

class Route {

    protected $model;
    protected $children = [];
    protected $siblings = [];
    protected $fields = [];
    protected $to;
    protected $refs = [];
    protected $meta;
    protected $state;
    protected $dictionary;
    protected $request;
    protected $accept = [];
    protected $actions = [];

    function __construct($request, $accept) {
        $this->request = $request;
        $this->accept = $accept;
    }

    function __set($name, $val) {
        if (property_exists($this, $name)) $this->$name = $val;
    }


    function __get($name) {
        if (property_exists($this, $name)) return $this->$name;
    }

    function updateAction($action, $label) {
        if (isset($this->actions[$action])) $this->actions[$action] = $label;
    }


    function updateChild($name, $label) {
        if (isset($this->children[$name])) $this->children[$name] = $label;
    }

    function updateSibling($name, $label) {
        if (isset($this->siblings[$name])) $this->siblings[$name] = $label;
    }

   

    function toArr() {
        $arr=[
            "model"=>$this->model, 
            "state"=>$this->state, 
            "fields"=>$this->fields, 
            "children"=>$this->children, 
            "siblings"=>$this->siblings,
            "actions"=>$this->actions
        ];

        if ($this->meta->to) $arr["to"] = $this->to->toArr();
        $arr["refs"] = [];
        foreach($this->refs as $slug=>$ref) {
            $arr["refs"][$slug] = $ref->toArr();
        }

        return $arr;
    }


    function toResponse() {

        $this->fields = $this->meta->getAsSchema();
        $this->dictionary->applyToRoute($this, $this->state);

        $response = new Response();
        $response->setData($this->toArr());
        return $response;
    }
}