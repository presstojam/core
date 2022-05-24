<?php

namespace PressToJamCore;

use Slim\Exception\HttpNotFoundException;

class Hooks {

	private $calculated=array();
    private $actuators=array();
    private $calculated_assets=array();
    private $routes=array();
	

	function __construct($link = "") {
        $hook = $this;
        if ($link) {
            if (file_exists($link)) {
                include($link);
            }
        }
	}
	
	function addCalculated($action, $callback)
	{
       $this->calculated[$action] = $callback;
	}

    function addActuators($action, $callback)
	{
        $this->actuators[$action] = $callback;
	}

    function addCalculatedAssets($action, $callback)
	{
        $this->calculated_assets[$action] = $callback;
	}
	
    function addRoute($route, $callback) {
        $this->routes[$route] = $callback;
    }
	
	function doCalculate($action, $obj)
	{
		if (isset($this->calculated[$action]))
		{
            $func = $this->calculated[$action];
            $func($obj);
		}
	}

    function doActuator($action, $model, $orig = null)
	{
		if (isset($this->actuators[$action]))
		{
            $func = $this->actuators[$action];
            $func($model, $orig);
		}
	}

    function doCalculateAsset($action, $obj) {
        if (isset($this->calculated_assets[$action]))
		{
            $func = $this->calculated_assets[$action];
            $func($obj);
		}
    }

    function runRoute($route, $request, $response, $container) {
        if (isset($this->routes[$route])) {
            return $this->routes[$route]($request, $response, $container);
        } else {
            throw new HttpNotFoundException($request);
        }
    }

}