<?php

namespace PressToJamCore\Configs;

class Factory {

    static function createS3Writer($config = null) {
        if (!$config) {
            $config = new AWS();
            $config->resource = "presstojamassets";
            $config->prefix = trim($_ENV['s3bucket'], "/") . "/";
            if (isset($_ENV['s3path'])) {
                $config->prefix .= rtrim($_ENV['s3path'], "/") . "/";
            }
        }
        return new \PressToJamCore\Services\AmazonS3Host($config);
    }


    static function createSQSHandler($config = null) {
        if (!$config) {
            $config = new AWS();
            $config->resource = $_ENV['sqsarn'];
        }
        return new \PressToJamCore\Services\SQSHandler($config);
    }


    static function createCloudFrontManager($config = null) {
        if (!$config) {
            $config = new AWS();
            $config->resource = $_ENV['cfdistid'];
        }
        return new \PressToJamCore\Services\CloudFrontManager($config);
    }


    static function createJWT($config = null) {
        if (!$config) {
            $config = new JWT();
            $config->secret = $_ENV['jwtkey'];
        }
        return new \PressToJamCore\JWTToken($config);
    }


    static function createPDO($config = null) {
        if (!$config) { 
            $config = new PDO();
            $config->host = $_ENV['dbhost'];
            $config->name = $_ENV['dbname'];
            $config->user = $_ENV['dbuser'];
            $config->pass = $_ENV['dbpass'];
            $config->port = $_ENV['dbport'];
        }
       
        $dsn = 'mysql:dbname=' .$config->name . ';host=' . $config->host;
        if ($config->port) $dsn .= ';port=' . $config->port;
        $dsn .= ';charset=utf8';

        $options = [];
        if ($config->cert) {
            $options = array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                \PDO::MYSQL_ATTR_SSL_CA => $configs->cert,
                \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            );
        }
        $pdo = new \PDO($dsn, $config->user, $config->pass, $options);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }


}