<?php
namespace PressToJamCore;

class Factory {

    static function camelCase($str) {
        return str_replace('-', '', ucwords($str, "-"));
    }

    static function createMeta($meta_name) {
        $meta_name = "\MetaCollections\\" . self::camelCase($meta_name);
        return new $meta_name();
    }

    static function createProfile($user) {
        $profile = $user->user;
        $class_name = "\PressToJam\Profile\\" . self::camelCase($profile);
        return new $class_name();
    }

    static function createRoute($model, $user, $params) {
        $class_name = "\PressToJam\Routes\\" . self::camelCase($model);
        return new $class_name($user, $params);
    }

    static function createModel($model, $user, $pdo, $params, $hooks) {
        $class_name = "\PressToJam\Models\\" . self::camelCase($model);
        return new $class_name($user, $pdo, $params, $hooks);
    }


    static function createReference($model) {
        $class_name = "\PressToJam\References\\" . self::camelCase($model);
        return new $class_name();
    }


    static function createRepo($model, $user, $pdo, $params, $hooks = null) {
        $class_name = "\PressToJam\Repos\\" . self::camelCase($model);
        return new $class_name($user, $pdo, $params, $hooks);
    }
}