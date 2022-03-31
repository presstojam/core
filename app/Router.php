<?php
namespace PressToJamCore;

class Router
{
    protected $method;
    protected $domain;
    protected $params;
    protected $route;

    function __construct() {
        $url = "";
        if (isset($_SERVER['SCRIPT_URI'])) {
            $url = parse_url($_SERVER['SCRIPT_URI']);
        } else if (isset($_SERVER['REQUEST_URI'])) {
            $url = parse_url($_SERVER['REQUEST_URI']);
        }
        $this->domain = trim($url['path'], "/");
        $this->method = strtolower($_SERVER['REQUEST_METHOD']);
        $this->route = $this->method . "::" . $this->domain;
    }

    function __get($key) {
        if (property_exists($this, $key)) return $this->$key;
    }


    public function parseParams()
    {
        $request = array();
        if ($this->method == "get") {
            $request = $_GET;

            foreach($request as $key=>$value) {
                if ($key == "graph") {
                    $vals = json_decode($value, true);
                    unset($request["graph"]);
                    foreach($vals as $key=>$nval) {
                        $request[$key] = $nval;
                    }
                }
            }
        } elseif ($this->method == "post") {
            $request = $_POST;
            if (count($request) == 0) {
                //check if any other data is coming in via json
                $str = file_get_contents('php://input');
                $json = json_decode($str, true);
                if ($json and $json != $str) {
                    $request = $json;
                } else {
                    parse_str($str, $request);
                }
            }
        } else {
            $str = file_get_contents('php://input');
            $json = json_decode($str, true);
            if ($json and $json != $str) {
                $request = $json;
            } else {
                parse_str($str, $request);
            }
        }

        return new Request($request);
    }
}
