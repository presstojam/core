<?php
namespace PressToJamCore;


class Route extends ShapeHandler
{

    protected $collection;
    protected $params;

    function __construct($user, $params) {
        parent::__construct($user);
        $this->params = $params;
    }


  
    public function setGetRoute() {
        $this->setStructure($this->collections[""], $this->params->to);
        $fields = [];
        if (count($this->params->fields) > 0) {
            foreach($this->params->fields as $field) {
                $slug = $this->getCollectionName($field);
                if (!isset($fields[$slug])) $fields[$slug] = [];
                $fields[$slug][] = $this->getFieldName($field);
            }
        } else {
            foreach($this->collections as $slug=>$col) {
                if (!isset($fields[$slug])) $fields[$slug] = [];
                $fields[$slug][] = (!$slug) ? "*" : "summary";
            }
        }
        
        $this->setFields($this->output_shape, $fields);
    
        $fields =$this->output_shape->fields;
        foreach($fields as $slug=>$field) {
            if (strpos($slug, "--owner") !== false) {
                unset($fields[$slug]);
            }
        }
        $this->output_shape->fields = $fields;
    }

}