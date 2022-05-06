<?php
namespace PressToJamCore;

use \Dflydev\FigCookies\FigRequestCookies;
use \Dflydev\FigCookies\FigResponseCookies;


class UserProfile {
    protected $user = "public";
    protected $id = 0;
    protected $profile = null;
    protected $lang = null;
    protected $permissions = [];
    protected $routes = [];
    private $refresh_minutes = 86400;
    private $auth_minutes = 15;


    function __construct()
    {
      // The expensive process (e.g.,db connection) goes here.
        $lang = new \PressToJam\Dictionary\Languages();
        $this->lang = $lang->getDefault();
    }


    function __set($key, $value) {
        if (property_exists($this, $key)) $this->$key = $value;
    }

    function __get($key) {
        if (property_exists($this, $key)) return $this->$key;
    }


    function saveCookie($name, $value, $expires) {
        return \Dflydev\FigCookies\SetCookie::create($name)
        ->withValue($value)
        ->withExpires($expires)
        ->withPath('/')
        ->withSecure(true)
        ->withHttpOnly(true)
        ->withSameSite(\Dflydev\FigCookies\Modifier\SameSite::none());
    }


    function initFromPayload($palyoad) {
        foreach($payload as $key=>$val) {
            $this->$key = $val;
        }
    }

    function save($response) {
        $token = Configs\Factory::createJWT();
        $access_token = $token->encode($this->toPayload(), $this->auth_minutes);
        $refresh_token = $token->encode($this->toPayload(), $this->refresh_minutes );
      
        FigResponseCookies::set(
            $response, 
            $this->saveCookie("api-auth", $access_token, time() + ($this->auth_minutes * 60))
        );

        FigResponseCookies::set(
            $response, 
            $this->saveCookie("api-refresh", $refresh_token, time() + ($this->refresh_minutes * 60))
        );
    }


    function switchTokens($request) {
        $refresh = FigRequestCookies::get($request, "api-refresh");
        $auth = FigRequestCookies::get($request, "api-auth");
        $token = Configs\Factory::createJWT();
        $payload = $token->decode($refresh);
        if ($payload) {
            $payload = $token->encode($payload, 15);
            return $auth->withValue($payload)
            ->withExpires(time() + ($this->auth_minutes * 60));
        } else {
            $this->logout();
        }
    }


    function toPayload() {
        return [
            "user"=>$this->user, 
            "id"=>$this->id, 
            "lang"=>$this->lang, 
            "profile"=>$this->profile
        ];
    }


    function getDictionary() {
        
    }


    function logout($response) {
        FigResponseCookies::remove($response, "api-auth");
        FigResponseCookies::remove($response, "api-refresh");
    }


    function validate($request) {
        //check if a default user has been set
        $auth = FigRequestCookies::get($request, "api-auth");
        //otherwise check if set via cookie
        if ($auth) {
            $token = Configs\Factory::createJWT();
            $payload = $token->decode($auth->getValue());
            if (!$payload) {
                echo "Token expired?";
                throw "Token  expired";
            } else {
                $user->initFromPayload($payload);
            }
        } 
    }

  

    function hasPermission($model, $state = null) {
        if (!isset($this->permissions[$model])) return false;
        else if ($state AND !in_array($state, $this->permissions[$model])) return false;
        return true;
    }
    
    
    function getRoute($model, $state) {
        return (!isset($this->routes[$model]) OR !isset($this->routes[$model][$state])) ? [] : $this->routes[$model][$state];
    }
}