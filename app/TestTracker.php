<?php

namespace PressToJamCore;


class TestTracker {

    static private $logs=array();
    private $seed_data = array();

    function __set($name, $val) {
        if ($name == "seed_data") $this->$name = $val;
    }

    function __get($name) {
        if ($name == "seed_data") return $this->$name;
    }


    function compare($data) {
        foreach($this->seed_data as $key=>$val) {
            if ($data->$key != $val) {
                return false;
            }
        }

        return true;
    }

    function addLog($log) {
        self::$logs[] = $log;
    }


    function setLog($outcome, $longer_desc = "") {
        $str = "Result: ";
        $str .= ($outcome) ? "PASSED" : "FAILED";
        $str .= "--------------------------";
        $str .=  $longer_desc;
        self::$logs[] = $str;
    }
    

    function createNumberSeed($cell) {
        return rand($cell->min, $cell->max);
    }


    function createAssetSeed($cell) {
        $exp = explode("|", $cell->contains);
        $num = array_rand($exp);
        return array("name"=>"." . $exp[$num], "size"=>rand($cell->min, $cell->max));
    }


    function randPad($str, $max) {
        $alphabet = range('A','Z');
        shuffle($alphabet);
        $num = rand(5, 10);
        $pad = implode("", array_splice($alphabet, $num));
        return str_pad($str, $max, $pad, STR_PAD_BOTH);
    }


    function createStringSeed($cell) {

 
        $strings=array();
        $parts=explode("|", $cell->contains);
        foreach($parts as $part) {
            if ($part AND $part[0] == "/") {
                $strings[] = trim($part, "/\\");
            } else {
                $strings[] = $part;
            }
        }

        if (count($strings) == 0) {
            return $this->randPad("", $cell->max);
        }

        $pos = array_rand($strings);
        return $this->randPad($strings[$pos], $cell->max);
    }


    function print() {
        echo implode("\n", self::$logs);
    }
}