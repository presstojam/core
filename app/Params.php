<?php
namespace PressToJamCore;

class Params
{
    private $data=[]; //data to be used in the where or having clause
    private $sort = []; //key => value
    private $fields = []; //should end up as table => []
    private $group = []; //should end up as table => []
    private $limit;
    private $page = 0;
    private $debug = false;
    private $to;
    private $children=[];
    private $history;
    private $key=0;
    protected $raw;
   
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        } else {
            return null;
        }
    }

    public function __set($name, $val)
    {
        if (property_exists($this, $name)) {
            return $this->$name = $val;
        }
    }


    public function apply($params)
    {
        foreach ($params as $key=>$val) {
            if ($key == "--id" or $key == "--parentid") {
                $this->data[$key] = $val;
                continue;
            }
        
            if (!is_array($val)) {
                $decoded_val = json_decode($val);
                if ($decoded_val !== null) {
                    $val = $decoded_val;
                }
            }
            
            if (strpos($key, "__") === 0) {
                $key = substr($key, 2);
                $this->$key = $val;
            } else {
                $this->data[$key] = $val;
            }
        }
    }
}