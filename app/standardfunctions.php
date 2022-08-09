<?php
namespace PressToJamCore;

if (! function_exists(__NAMESPACE__ . '\regAutoload')) {

function regAutoload($namespace, $base) {
    //register psr-4 autoload
    spl_autoload_register(function ($class_name) use ($namespace, $base) {
        $parts = explode("\\", $class_name);
        $file = $base .  "/";
        $onamespace = array_shift($parts);
        if ($onamespace == $namespace) {
            $file .= implode("/", $parts) . ".php";
            if (file_exists($file)) {
                require_once($file);
                return;
            } else {
                echo "Can't find file " . $file;
            }
        }
    });
}


}



if (! function_exists(__NAMESPACE__ . '\kebabCase')) {

    function kebabCase($name) {
        $name = preg_replace_callback(
            "/[A-Z]/", 
            function($matches) {
                return "-" . strtolower($matches[0]);
            },
            $name
        );
        return str_replace(["___", "_", "-i-d"], ["/", "-", "-id"], $name);
    }
}



if (!function_exists(__NAMESPACE__ . '\env')) {

    function env($key, $default = null) {
        return (isset($_ENV[$key])) ? $_ENV[$key] : $default;
    }
    
}