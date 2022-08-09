<?php

namespace PressToJamCore;

class VendorFactory {

    static function createS3Writer() {
        return new \PressToJamCore\VendorWrappers\AmazonS3Host(Configs::s());
    }


    static function createSQSHandler($config = null) {
        $configs = Configs::s()->getConfigGroup("aws");
        return new \PressToJamCore\Services\SQSHandler(Configs::s());
    }


    static function createCloudFrontManager($config = null) {
        $configs = Configs::s()->getConfigGroup("aws");
        return new \PressToJamCore\VendorWrappers\CloudFrontManager(Configs::s());
    }


    static function createJWT($config = null) {
        $configs = Configs::s()->getConfigGroup("jwt");
        return new \PressToJamCore\VendorWrappers\JWTToken(Configs::s());
    }


    static function createPDO() {
        $configs = Configs::s();
        $configs->isRequired("pdo", "name");
        $configs->isRequired("pdo", "host");
        $configs->isRequired("pdo", "user");
        $configs->isRequired("pdo", "pass");

       
        $dsn = 'mysql:dbname=' .$configs->getConfig("pdo", "name") . ';';
        $dsn .= 'host=' . $configs->getConfig("pdo", "host") . ';';
        $dsn .= 'port=' . $configs->getConfig("pdo", "port", 3306) . ';';
        $dsn .= 'charset=utf8';

        $options = [];
        if ($configs->hasConfig("pdo", "cert")) {
            $options = [
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                \PDO::MYSQL_ATTR_SSL_CA => $configs->getConfig("pdo", "cert"),
                \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ];
        }
        $pdo = new \PDO($dsn, $configs->getConfig("pdo", "user"), $configs->getConfig("pdo", "pass"), $options);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }


}