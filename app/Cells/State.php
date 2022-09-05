<?php

namespace PressToJamCore\Cells;

class State
{
    protected $depends_on;
    protected $depends_val;
    protected $field;


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


    function toSchema() {
        $arr=[];
        $arr["depends_on"] = $this->depends_on;
        $arr["depends_val"] = $this->depends_val;
        $arr["data"] = $this->field->toSchema();
        return $arr;
    }
}