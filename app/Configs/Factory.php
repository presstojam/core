<?php

namespace PressToJamCore\Configs;

class Factory {

    static function createS3Writer() {
        $conf = GlobalConfigs::s();
        $config = $conf->getConfig("awss3");
        return new \PressToJamCore\Services\AmazonS3Host($config);
    }

    static function createPublicS3Writer() {
        $conf = GlobalConfigs::s();
        $config = $conf->getConfig("awss3public");
        return new \PressToJamCore\Services\AmazonS3Host($config);
    }


    static function createSQSHandler() {
        $conf = GlobalConfigs::s();
        $config = $conf->getConfig("awssqs");
        return new \PressToJamCore\Services\SQSHandler($config);
    }


    static function createCloudFrontManager() {
        $conf = GlobalConfigs::s();
        $config = $conf->getConfig("awscloudfront");
        return new \PressToJamCore\Services\CloudFrontManager($config);
    }


    static function createJWT() {
        $conf = GlobalConfigs::s();
        $jwt = $conf->getConfig("jwt");
        return new \PressToJamCore\JWTToken($jwt);
    }


    static function createPDO() {
        $conf = GlobalConfigs::s();
        $configs = $conf->getConfig("pdo");
       
        $dsn = 'mysql:dbname=' .$configs->name . ';host=' . $configs->host;
        if ($configs->port) $dsn .= ';port=' . $configs->port;
        $dsn .= ';charset=utf8';

        $options = [];
        if ($configs->cert) {
            $options = array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                \PDO::MYSQL_ATTR_SSL_CA => $configs->cert,
                \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            );
        }
        $pdo = new \PDO($dsn, $configs->user, $configs->pass, $options);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }


    static function getUser() {
        $conf = GlobalConfigs::s();
        return $conf->getConfig("user");
    }

}