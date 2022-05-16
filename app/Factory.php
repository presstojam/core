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

    static function createPerms($user) {
        $profile = $user->user;
        if ($user->role) $profile .= "_" . $user->role;
        $class_name = "\PressToJam\Profile\Perms\\" . self::camelCase($profile) . "Perms";
        return new $class_name();
    }

    static function createNav($user) {
        $profile = $user->user;
        if ($user->role) $profile .= "_" . $user->role;
        $class_name = "\PressToJam\Profile\Nav\\" . self::camelCase($profile) . "Nav";
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


    static function createRepo($model, $user, $pdo, $params, $hooks) {
        $class_name = "\PressToJam\Repos\\" . self::camelCase($model);
        return new $class_name($user, $pdo, $params, $hooks);
    }
}