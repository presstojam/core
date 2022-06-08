<?php

namespace PressToJamCore\Cells;

class State
{
    protected $type;

    protected $unique = false;
    protected $required = false;
    protected $reference = null;

    protected $min= 0;
    protected $max = 4294967295;
    protected $contains = "";
    protected $not_contains = "";
  
    protected $default = "";

    protected $depends_on;
    protected $val;
     


    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
    }
}