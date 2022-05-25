<?php
namespace PressToJamCore;


class ShapeHandler
{
    protected $user;
    protected $input_shape;
    protected $output_shape;
    protected $collections = [];


    public function __construct($user)
    {
        $this->user = $user;
        $this->input_shape = new DataShape();
    }


    function getCollectionName($slug) {
        $pos = strripos($slug, "/");
        if ($pos === false) return "";
        else return substr($slug, 0, $pos + 1); //return full slug with trailing slash
    }

    function getFieldName($slug) {
        $pos = strripos($slug, "/");
        if ($pos === false) return $slug;
        return substr($slug, $pos + 1); 
    }


    function createCell($collection, $alias) {
        $field = $collection->getFromAlias($alias);
        if (!$field) 
            throw new Exceptions\PtjException("Can't find field with alias of  " . $alias);
        $cell = new Cells\DataCell($field);
        return $cell;
    }


    function setFields($shape, $fields) {
        foreach($fields as $slug=>$names) {
            $collection = $this->collections[$slug];
            foreach ($names as $alias) {
                if ($alias == "*") {
                    $aliases = $collection->getAllAliases();
                    foreach($aliases as $calias) {
                        $cell = $this->createCell($collection, $calias);
                        if ($cell->encrypted) $shape->addFilter($slug . $calias, $cell);
                        else $shape->addField($slug . $calias, $cell);
                    }
                } else if ($alias == "*summary") {
                    $aliases = $collection->getSummaryAliases();
                    foreach($aliases as $calias) {
                        $cell = $this->createCell($collection, $calias);
                        if ($cell->encrypted) $shape->addFilter($slug . $c, $cell);
                        else $shape->addField($slug . $calias, $cell);
                    }
                } else {
                    $cell =  $this->createCell($collection, $alias);
                    if ($cell->encrypted) {
                        $shape->addFilter($slug . $alias, $cell);
                    } else {
                        $shape->addField($slug . $alias, $cell);
                    }
                }
            }
        }
    }


    function setReferences() {
        $fields = [];
        foreach($this->output_shape->fields as $slug=>$cell) {
            if (get_class($cell->meta_field) == "\PressToJamCore\Cells\IdCell") {
                if (!$cell->meta_field->is_parent and !$cell->meta_field->is_primary and $cell->meta_field->reference) {
                    $this->output_shape->addRelationship($slug, $cell);
                    $this->collections[$slug] = $cell->meta_field->reference;
                    $fields[$slug][] = "*summary";
                }
            }
        }
        $this->setFields($this->output_shape, $fields);
    }


    function setFilterFields($data) {
        foreach($data as $slug=>$val) {
            $colslug = $this->getCollectionName($slug);
            if (!isset($this->collections[$colslug])) {
                throw new Exceptions\PtjException("Can't apply filter field " . $slug . " to collection that hasn't been set: " . $slug);
            }
            $this->input_shape->addFilter($slug, $this->createCell($this->collections[$colslug], $this->getFieldName($slug)));
        }
    }


    function setStructure($collection, $to)
    {
        $this->collections[$collection->slug] = $collection;
        if (!$this->output_shape) $this->output_shape = new DataShape();
        if ($to and $to . "/" != $collection->slug) {
            if ($collection->hasParent()) {
                $parent = $collection->parent();
                $this->input_shape->addRelationship($collection->slug . $parent->slug, $parent);
                $this->setStructure($parent->reference, $to);
            } else if ($collection->hasOwner()) {
                $owner = $collection->owner();
                $this->input_shape->addRelationship($collection->slug . $owner->slug, $owner);
                $this->setStructure($owner->reference, $to);
            }
        }
    }


    function setChildren($collection, $children)
    {
        if (!in_array($collection->slug, $children)) return;

        $id = $collection->primary();
        $refs = [];
        foreach($id->reference as $child) {
            if (in_array($child->slug, $children)) {
                $refs[] = $child;
            }
        }
        $id->reference = $refs;
        $this->output_shape->addRelationship($id);
        foreach($id->reference as $child) {    
            $this->collections[$child->slug] = $child;
            $this->setChildren($child, $children, $fields);
        }
    }


    function buildSecurity($collection)
    {
        $field = null;
        if ($collection->hasOwner()) {
            $field = $collection->owner();
        } else if (!$collection->hasParent()) {
            $field = $collection->primary();
        } 

        if ($field) {
            $cell = new Cells\DataCell($field);
            $cell->map($this->user->id);
            $cell->immutable = true;
            $this->input_shape->addFilter($collection->slug . $cell->slug, $cell);
        } else {
            $parent = $collection->parent();
            $this->input_shape->addRelationship($collection->slug . $parent->slug, $parent);
            $this->buildSecurity($parent->reference);
        }
    }

    
}