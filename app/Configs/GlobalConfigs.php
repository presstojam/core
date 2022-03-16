<?php

namespace PressToJamCore\Configs;

class GlobalConfigs {

    private $configs=array();
    private static $obj;

    private final function __construct() {
    }

    public static function s() {
        if (!isset(self::$obj)) {
            self::$obj = new GlobalConfigs();
        }
        return self::$obj;
    }


    function register($type, $config) {
        $this->configs[$type] = $config;
    }


    function getConfigs() {
        return $this->configs;
    }


    function getConfig($type) {
        if (!isset($this->configs[$type])) {
            throw new \Exception(__FILE__ . "::" . __LINE__ . " Trying to call configs for " . $type . " before it is set");
        } else {
            return $this->configs[$type];
        }
    }

    
}