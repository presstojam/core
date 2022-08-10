<?php

namespace PressToJamCore;

class Configs {
    private $configs=array();
    private static $obj;

    private final function __construct() {
    }

    public static function s() {
        if (!isset(self::$obj)) {
            self::$obj = new Configs();
        }
        return self::$obj;
    }


    function loadFile($file) {
        $file_configs = include($file);
        $this->configs = array_merge($this->configs, $file_configs);
    }

   

    function setConfigGroup($cat, $configs) {
        $this->configs[$cat] = $configs;
    }

    function getConfigGroup($cat) {
        if (!isset($this->configs[$cat])) return [];
        else return $this->configs[$cat];
    }

    function setConfig($cat, $key, $value) {
        if (!isset($this->configs[$cat])) $this->configs[$cat] = [];
        $this->configs[$cat][$key] = $value;
    }


    function getConfig($cat, $key, $default = null) {
        if (!$this->hasConfig($cat, $key)) return $default;
        else return $this->configs[$cat][$key];
    }


    function hasConfig($cat, $key) {
        if (!isset($this->configs[$cat]) OR !isset($this->configs[$cat][$key])) return false;
        else return true;
    }

    function isRequired($cat, $key) {
        if (!isset($this->configs[$cat]) OR !isset($this->configs[$cat][$key])) {
            throw new \Exception($cat . " configs error: " . $key . " is required");
        }
    }
}