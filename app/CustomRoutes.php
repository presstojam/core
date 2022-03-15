<?php
namespace PressToJamCore;


class CustomRoutes
{
    static private $routes = [];
   
    static public function reg($route, $callback)
    {
        self::$routes[$route] = $callback;
    }

    static public function run($route, $user, $request)
    {
        if (isset(self::$routes[$route])) {
            self::$routes[$route]($user, $request);
            return true;
        }
        return false;
    }
}

