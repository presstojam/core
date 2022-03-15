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


    function getAllConfigs($type) {
        return $this->configs[$type];
    }


    function getConfig($type) {
        if (!isset($this->configs[$type])) {
            throw new \Exception(__FILE__ . "::" . __LINE__ . " Trying to call configs for " . $type . " before it is set");
        } else {
            return $this->configs[$type];
        }
    }

    function registerFromEnv($env) {
        if (isset($env['dbhost'])) {
            $config = new PDO();
            $config->host = $env['dbhost'];
            $config->name = $env['dbname'];
            $config->user = $env['dbuser'];
            $config->pass = $env['dbpass'];
            $config->port = $env['dbport'];
            $this->register("pdo", $config);
        }


        if (isset($env['jwtkey'])) {
            $config = new JWT();
            $config->secret = $env['jwtkey'];
            $this->register("jwt", $config);
        }

        if (isset($env['s3bucket'])) {
            $config = new AWS();
            $config->resource = $env['s3bucket'];
            if (isset($env['s3path'])) {
                $config->prefix = rtrim($env['s3path'], "/") . "/";
            }
            $this->register("awss3", $config);
        }

        if (isset($env['s3publicbucket'])) {
            $config = new AWS();
            $config->resource = $env['s3publicbucket'];
            $config->public = true;
            if (isset($_ENV['s3publicpath'])) {
                $config->prefix = $env['s3publicpath'];
            }
            $this->register("awss3public", $config);
        }

        if (isset($env['cfdistid'])) {
            $config = new AWS();
            $config->resource = $env['cfdistid'];
            $this->register("awscloudfront", $config);
        }

        if (isset($env['sqsarn'])) {
            $config = new AWS();
            $config->resource = $env['sqsarn'];
            $this->register("awssqs", $config);
        }
    }
}