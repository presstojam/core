<?php

namespace PressToJamCore;

class DictionaryField implements \JsonSerializable
{
    public $label;
    public $hint;
    public $title;
    public $errors;
    public $placeholder;
    public $actions = [];
    

    function jsonSerialize() {
        $arr = [];
        if ($this->label) $arr["label"] = $this->label;
        if ($this->actions) $arr["actions"] = $this->actions;
        if ($this->hint) $arr["hint"] = $this->hint;
        if ($this->title) $arr["title"] = $this->title;
        if ($this->errors) $arr["errors"] = $this->errors;
        if ($this->placeholder) $arr["placeholder"] = $this->placeholder;
        return $arr;
    }
};


class Dictionary implements \JsonSerializable {

    protected $label = "";
    protected $title = "";
    protected $hint = "";
    protected $errors = [];
    protected $actions = [];
    protected $fields=[];
    protected $children = [];
    protected $parent = "";
    public $states = [];
    

    function jsonSerialize() {
        return [
            "label"=>$this->label,
            "title"=>$this->title,
            "hint"=>$this->hint,
            "errors"=>$this->errors,
            "actions"=>$this->actions,
            "fields"=>$this->fields,
            "children"=>$this->children,
            "parent"=>$this->parent,
            "states"=>$this->states
        ];
    }   
}