<?php

namespace PressToJamCore\Cells;

class AssetCell extends Cell {

    private $token;
    private $size = 0;
    private $chunk_size = 0;
    private $tmp_file_dir;
    private $name_template = "";
  

    function __set($name, $value) {
        if (property_exists($this, $name)) $this->$name = $value;
    }

    function __get($name) {
        if ($name == "param_type") return \PDO::PARAM_STR;
        else if (property_exists($this, $name)) return $this->$name;
        else return null;
    }
    
    function __toString() {
        return $this->value;
    }

    function setValidation($min, $max, $contains = "", $not_contains = "") {
        $this->min = $min;
        $this->max = $max;
        $this->contains = $contains;
        $this->not_contains = $not_contains;
    }

    function getKeyName($id, $value) {
        return str_replace("%id", $id, $value);
    }

    function convertKeyName($id) {
        $this->value = $this->getKeyName($id, $this->value);
    }

   
    function tempLocation() {
        return tempnam($this->tmp_file_dir);
    }

   
    function map($val) {
        if (is_array($val)) {
            if (isset($val['size'])) {
                $this->size = $val['size'];
            }

            $extension = "";
            if (isset($val['name'])) {
                $extension = \pathinfo($val['name'], \PATHINFO_EXTENSION);
            }
            $this->value = str_replace("%ext", $extension, $this->name_template);
        } else {
            $this->value= $val;
        }
    }


    function validate() {
        if ($this->isOn()) {
            $rule = $this->validateSize($this->size);
            if ($rule != ValidationRules::OK) {
                return $rule;
            }
            $rule = $this->validateValue($this->value);
            return $rule;
        } else {
            return ValidationRules::OK;
        }
    }


    public function writeFile($data) {
        $ext = \pathinfo($this->value, \PATHINFO_EXTENSION);
        if (!$ext) {
            throw new \Exception("No extension for file");
        }
        $writer = \PressToJamCore\Configs\Factory::createS3Writer();
        if (!is_string($data)) {
            $data =  pack('C*', ...$data);
        }
        $writer->push($this->value, $data);
    } 


    public function writeChunk($chunk, $data) {
        $chunk_name = $this->tempLocation();
        $temp_fp = fopen($chunk_name, "w");
        if (!is_string($data)) {
            $data =  pack('C*', ...$data);
        }
        fwrite($temp_fp, $data);
        return basename($chunk_name);
    }

    public function completeMultipartFileUpload($chunks) {
        $big_file = $this->tempLocation();
        $temp_fp = fopen($big_file, "a");
        foreach($chunks as $chunk) {
            fwrite($temp_fp, file_get_contents($this->tmp_file_dir . "/" . $chunk));
            unlink($this->tmp_file_dir);
        }

        $writer = \PressToJamCore\Configs\Factory::createS3Writer();
        $writer->push($this->value, file_get_contents($big_file));
        unlink($big_file);
    }


    public function removeAsset() {
        $writer = \PressToJamCore\Configs\Factory::createS3Writer();
        $writer->remove($this->value);
    }


    public function copyAsset($old_file) {
        $writer = \PressToJamCore\Configs\Factory::createS3Writer();
        $writer->copy($this->value, $old_file);
    }


    public function view() {
        //can set header from extension
        $writer = \PressToJamCore\Configs\Factory::createS3Writer();
        return $writer->get($this->value);
    }


    function toOutput() {
        return $this->value;
    }

    function export($id = null) {
        $this->convertKeyName($id);
        return $this->value;
    }

    function reset() {
        $this->value = null;
    }

}