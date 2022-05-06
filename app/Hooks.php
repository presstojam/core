<?php

namespace PressToJamCore;

class Hooks {

	private $calculated=array();
    private $actuators=array();
    private $calculated_assets=array();
    private $routes=array();
	

	function __construct() {
	
	}
	
	function addCalculated($action, $callback)
	{
       self::$calculated[$action] = $callback;
	}

    function addActuators($action, $callback)
	{
        self::$actuators[$action] = $callback;
	}

    function addCalculatedAssets($action, $callback)
	{
        self::$calculated_assets[$action] = $callback;
	}
	
    function addRoute($route, $callback) {
        self::$routes[$route] = $callback;
    }
	
	function doCalculate($action, $obj)
	{
		if (isset(self::$calculated[$action]))
		{
            $func = self::$calculated[$action];
            $func($obj);
		}
	}

    function doActuator($action, $model, $orig = null)
	{
		if (isset(self::$actuators[$action]))
		{
            $func = self::$actuators[$action];
            $func($model, $orig);
		}
	}

    function doCalculateAsset($action, $obj) {
        if (isset(self::$calculated_assets[$action]))
		{
            $func = self::$calculated_assets[$action];
            $func($obj);
		}
    }

    function runRoute($route, $user, $request) {
        if (isset(self::$routes[$route])) {
            return self::$routes[$route]($user, $request);
        } else {
            return false;
        }
    }

}