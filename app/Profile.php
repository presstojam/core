<?php
namespace PressToJamCore;

class Profile {

    protected $permissions = [];


    function hasPermission($model, $state, $method) {
        if (!isset($this->permissions[$model])) return false;
        if (!isset($this->permissions[$model][$state])) return false;
        if ($this->permissions[$model][$state] != $method) return false;
        return true;
    }


    function login($user, $pdo, $params, $type) {
        $params->fields=["--id", "password", "type"];
        $params->to = null;
        $params->limit = 1;
        if (!isset($params->data["password"]) OR !isset($params->data["username"]) OR !isset($params->data["type"])) {
            throw new Exceptions\PtjException("Incorrect parameters set");
        } 

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