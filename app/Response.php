<?php
namespace PressToJamCore;

class Response implements \JsonSerializable {

    private $data=array();
    private $errors=array();
    private $count=0;
    private $user;
    private $user_id;
    private $lang;
    static private $debug_data = [];
   

    public function __construct() {
    
    }

    function __set($name, $value) {
        $this->data[$name] = $value; //if set like this, will be declared from the database so use magic method
    }

    function __get($name) {
        if ($name == "errors") return $this->errors;
        else if ($name == "status") return $this->status();
        else return (isset($this->data[$name])) ? $this->data[$name] : null;
    }

    public function &getData($key = null) {
        if (!$key) return $this->data;
        else return $this->data[$key];
    }

    public function setUser($user, $id, $lang = "") {
        $this->user = $user;
        $this->user_id = $id;
        $this->lang = $lang;
    }

    public function setData(array $fields) {
        $this->data = $fields;
    }

    static public function setDebugData(array $fields) {
        self::$debug_data = array_merge(self::$debug_data, $fields);
    }

    public function addData($name, $value) {
        $this->data[$name] = $value;
    }

    public function addChild($cat, $name, $value) {
        if (!isset($this->data[$cat])) $this->data[$cat] = array($name=>$value);
        else $this->data[$cat][$name] = $value;
     }

    public function setErrors(array $errors) {
        $this->errors = $errors;
    }

    public function addErrors(array $errors) {
        $this->errors = array_merge($this->errors, $errors);
    }

    public function addError($name, $value) {
        $this->errors[$name] = $value;
    }

    public function status() {
       return (!$this->errors || count($this->errors) == 0) ? true : false;
    }

    public function setCount($count) {
        $this->count = $count;
    }



    function jsonSerialize() {
        $obj = new \StdClass;
        if ($this->status()) {
            $obj->__status = "SUCCESS";
            $obj->__count = $this->count;
            $obj->__debug = self::$debug_data;
            if ($this->user) {
                $obj->__profile = $this->user;
                $obj->__profile_id = $this->user_id;
                $obj->__profile_lang = $this->lang;
            } else {
                $obj->__profile = "public";
            }

            foreach($this->data as $key=>$val) {
                $obj->$key = $val;
            }
        } else {
            $obj->__status = "ERROR";
            foreach($this->errors as $key=>$val) {
                $obj->$key = $val;
            }
        }
        return $obj;
    }

  
}