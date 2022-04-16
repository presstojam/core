<?php

namespace PressToJamCore;

class DictionaryChapter
{
    public $label;
    public $hint;
    public $title;
    public $errors;
    public $placeholder;
    public $actions = [];

    function toArr() {
        $arr = [];
        if ($this->label) $arr["label"] = $this->label;
        if ($this->actions) $arr["actions"] = $this->actions;
        if ($this->hint) $arr["hint"] = $this->hint;
        if ($this->title) $arr["title"] = $this->title;
        if ($this->errors) $arr["errors"] = $this->errors;
        if ($this->placeholder) $arr["placeholder"] = $this->placeholder;
        return $arr;
    }
}


class Dictionary {

    protected $chapter;
    protected $fields=[];
    protected $children = [];
    protected $siblings = [];
    protected $states = [];

    
    function applyToRoute($route, $method) {

        $route->label = $this->chapter->label;

        foreach ($this->siblings as $slug=>$label) {
            $route->updateSibling($slug, $label);
        }

        foreach ($this->children as $slug=>$label) {
            $route->updateChild($slug, $label);
        }
        
        foreach($this->chapter->actions as $key=>$label) {
            $route->updateAction($key, $label);
        }

        $fields = $route->fields;
     
        foreach($this->fields as $slug=>$field) {
            if (isset($fields[$slug])) {
                $fields[$slug] = array_merge($fields[$slug], $field->toArr());
            }
        }
        $route->fields = $fields;

        /*
        $route->applyToRoute($this->states[$method]["chapter"]);
            $state->applyDictionary($this->states[$method]["chapter"]);
            $fields = $state->fields;
            foreach($this->fields as $slug=>$field) {
                if (isset($fields[$slug])) {
                    $fields[$slug] = array_merge($fields[$slug], $field->toArr());
                }
            }
            $state->fields = $fields;
        }
        */
    }
}