<?php

namespace PressToJamCore;

class Setup
{
    public static function regAutoload($base)
    {

        //register psr-4 autoload
        spl_autoload_register(function ($class_name) {
            $parts = explode("\\", $class_name);
            $file = $base .  "/";
            array_shift($parts);
            $file .= implode("/", $parts) . ".php";
            if (file_exists($file)) {
                require_once($file);
                return;
            }
        });
    }

    public static function loadConfigs($base)
    {
        $s = Configs\GlobalConfigs::s();

        $dotenv = \Dotenv\Dotenv::createImmutable($base);
        $dotenv->load();

        if (isset($_ENV['dbhost'])) {
            $config = new Configs\PDO();
            $config->host = $_ENV['dbhost'];
            $config->name = $_ENV['dbname'];
            $config->user = $_ENV['dbuser'];
            $config->pass = $_ENV['dbpass'];
            $config->port = $_ENV['dbport'];
            $s->register("pdo", $config);
        }
    
    
        if (isset($_ENV['jwtkey'])) {
            $config = new Configs\JWT();
            $config->secret = $_ENV['jwtkey'];
            $s->register("jwt", $config);
        }
    
        if (isset($_ENV['s3bucket'])) {
            $config = new Configs\AWS();
            $config->resource = $_ENV['s3bucket'];
            if (isset($_ENV['s3path'])) {
                $config->prefix = rtrim($_ENV['s3path'], "/") . "/";
            }
            $s->register("awss3", $config);
        }
    
        if (isset($_ENV['s3publicbucket'])) {
            $config = new Configs\AWS();
            $config->resource = $_ENV['s3publicbucket'];
            $config->public = true;
            if (isset($_ENV['s3publicpath'])) {
                $config->prefix = $_ENV['s3publicpath'];
            }
            $s->register("awss3public", $config);
        }
    
        if (isset($_ENV['cfdistid'])) {
            $config = new Configs\AWS();
            $config->resource = $_ENV['cfdistid'];
            $s->register("awscloudfront", $config);
        }
    
        if (isset($_ENV['sqsarn'])) {
            $config = new Configs\AWS();
            $config->resource = $_ENV['sqsarn'];
            $s->register("awssqs", $config);
        }
    }
}
