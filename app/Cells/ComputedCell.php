<?php
namespace PressToJamCore\Cells;

class ComputedCell {

    protected $value = null;
    protected $meta_field = null;
    protected $alias;

    function __construct($field) {
        $this->meta_field = $field;
        $this->value = $this->meta_field->default;
    }
}

