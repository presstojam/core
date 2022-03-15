<?php

namespace PressToJamCore;

class Hooks {

	static private $calculated=array();
    static private $actuators=array();
    static private $calculated_assets=array();
    static private $routes=array();
	

	function __construct() {
	
	}
	
	static function addCalculated($action, $callback)
	{
       self::$calculated[$action] = $callback;
	}

    static function addActuators($action, $callback)
	{
        self::$actuators[$action] = $callback;
	}

    static function addCalculatedAssets($action, $callback)
	{
        self::$calculated_assets[$action] = $callback;
	}
	
    static function addRoute($route, $callback) {
        self::$routes[$route] = $callback;
    }
	
	static function doCalculate($action, $obj)
	{
		if (isset(self::$calculated[$action]))
		{
            $func = self::$calculated[$action];
            $func($obj);
		}
	}

    static function doActuator($action, $model, $orig = null)
	{
		if (isset(self::$actuators[$action]))
		{
            $func = self::$actuators[$action];
            $func($model, $orig);
		}
	}

    static function doCalculateAsset($action, $obj) {
        if (isset(self::$calculated_assets[$action]))
		{
            $func = self::$calculated_assets[$action];
            $func($obj);
		}
    }

    static function runRoute($route, $user, $request) {
        if (isset(self::$routes[$route])) {
            return self::$routes[$route]($user, $request);
        } else {
            return false;
        }
    }

}