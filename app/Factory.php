<?php
namespace PressToJamCore;

class Factory {

    static function camelCase($str) {
        return str_replace('-', '', ucwords($tr, "-"));
    }

    static function createMeta($meta_name) {
        $meta_name = "MetaCollections\\" . self::camelCase($meta_name);
        return new $meta_name();
    }

    static function createProfile($profile) {
        $class_name = "PressToJam\Profiles\\" . self::camelCase($profile);
        return new $class_name();
    }
}