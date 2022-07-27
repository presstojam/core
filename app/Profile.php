<?php
namespace PressToJamCore;

class Profile {

    protected $routes = [];
    protected $model_perms = [];
    protected $route;


    function hasModelPermissions($model, $method) {
        if (!isset($this->model_perms[$model])) return false;
        if (!in_array($method, $this->model_perms[$model])) return false;
        return true;
    } 


    function hasRoutePermissions($route) {
        if (!isset($this->routes[$route])) return false;
      
        return true;
    }


    function isOwner($route) {
        return $this->routes[$route];
    }


    function getRoutePoint($route, $flow, $model) {
        if (!isset($this->routes[$route])) {
            throw new Exceptions\PtjException("Route " . $route . " doesn't exist");
        }
        if (!isset($this->routes[$route][$flow])) {
            throw new Exceptions\PtjException("Flow " . $route . "::" . $flow . " doesn't exist");
        }
        $class_name = "\PressToJam\Profile\Flows\\" . $this->routes[$route][$flow];
        $route = new $class_name();
        $route->{ "get" . Factory::camelCase($model)}();
        return $route;
    }


    function login($user, $pdo, $params, $type) {
        $params->fields=["--id", "password", "type"];
        $params->to = null;
        $params->limit = 1;

        
       
        if (!isset($params->data["password"]) OR !isset($params->data["username"])) {
            throw new Exceptions\PtjException("Incorrect parameters set");
        } 

        $params->data = ["username"=>$params->data["username"], "password"=>$params->data["password"]];        

        $repo = new \PressToJam\Repos\UserLogin($user, $pdo, $params);
        $obj = $repo->get();
        if ($obj) {
            $user->user = $obj->type;
            $user->id = $obj->{ "--id" };
        }
    }

    public function updatePasswordRequest($pdo, $params) {
        $params->fields = ["--id"];
        if (!isset($params->data["code"]) OR !isset($params->data["password"])) {
            throw new Exceptions\PtjException("Incorrect parameters");
        }
        $params->limit = 1;
        $repo = new \PressToJam\Repos\UserLogin($pdo, $params);
        $obj = $repo->get();
        if (!$obj) {
            throw new Exceptions\PtjException("This username was not recognised");
        }

        $nparams = new Params();
        $nparams->data = ["password"=>$params->data["password"], "id"=>$obj->{"--id"}];
        $model = new PressToJam\Models\UserLogin($pdo, $params);
        $model->update();
        return "success";
    }


    
    
    public function getResetPasswordRequest($username) {
        $field = new Cells\String();
        $params = new Params();
        $params->fields = ["--id"];
        $params->data = ["username"=>$username];
        $params->limit = 1;
        $repo = new \PressToJam\Repos\UserLogin($pdo, $params);
        $obj = $repo->get();
        if (!$obj) {
            throw new Exceptions\PtjException("This username was not recognised");
        }

        $params = new Params();
        $params->data = ["--whisper-id"=>$field->getRandom(75), "id"=>$obj->{"--id"}];
        $model = new \PressToJam\Models\UserLogin($pdo, $params);
        $model->update();
        return "success";
    }
}