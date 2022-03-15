<?php
namespace PressToJamCore;

class UserProfile {
    private $user = "public";
    private $id = 0;
    private $token_expired = false;
    private $lang = null;

    private static $instance = null;
  
    private function __construct()
    {
      // The expensive process (e.g.,db connection) goes here.
      $lang = new \PressToJam\Dictionary\Languages();
      $this->lang = $lang->getDefault();
    }

    static function s() {
        if (self::$instance == null) {
            self::$instance = new UserProfile();
        }
        return self::$instance;
    }

    function __set($key, $value) {
        if ($key == "user" OR $key == "id") $this->$key = $value;
    }

    function __get($key) {
        if ($key == "user" OR $key == "id") return $this->$key;
    }


    function validateUser() {
        //check if a default user has been set
        $user = Configs\Factory::getUser();
        if ($user) {
            $this->user = $user->user;
            $this->id = $user->id;
            $this->lang = $user->lang;
            return $this->user;
        }

        //otherwise check if set via cookie
        if (isset($_COOKIE['api-auth'])) {
            $token = Configs\Factory::createJWT();
            $payload = $token->decode($_COOKIE['api-auth']);
            if (!$payload) {
                $this->token_expired = $token->isExpired();
            } else {
                $this->user = $payload->user;
                $this->id = $payload->id;
                $this->lang = $payload->lang;
            }
        } else if (isset($_COOKIE['api-refresh'])) {
            $this->switchTokens();
        }
    }

    function save() {
        $lifetime_minutes = 15;
        $refresh_seconds = 86400; //24 hours - 60 * 60 * 24
        $token = Configs\Factory::createJWT();
        $access_token = $token->encode(array("user"=>$this->user, "id"=>$this->id, "lang"=>$this->lang), $lifetime_minutes);
        $refresh_token = $token->encode(array("user"=>$this->user, "id"=>$this->id, "lang"=>$this->lang), $refresh_seconds);
        $cookie_options =  array("path"=> "/",  "secure"=>true, "httponly"=>true, "samesite"=>"None");
        $cookie_options["expires"] = time() + ($lifetime_minutes * 60);
        setcookie("api-auth", $access_token, $cookie_options);
        $cookie_options["expires"] = time() + $refresh_seconds;
        //$cookie_options["path"] = "/core-switch-tokens";
        setcookie("api-refresh", $refresh_token, $cookie_options);
    }


    function switchTokens() {
        $response = new Responses\Response();
        $token = Configs\Factory::createJWT();
        $payload = $token->decode($_COOKIE['api-refresh']);
        if ($payload) {
            $this->user = $payload->user;
            $this->id = $payload->id;
            $this->save();
        } else {
            $this->logout();
        }
    }


    function logout() {
        $this->id = 0;
        $this->user = "public";
        $cookie_options =  array("expires"=>-1, "path"=> "/",  "secure"=>true, "httponly"=>true, "samesite"=>"None");
        setcookie("api-auth", null, $cookie_options);
        //$cookie_options["path"] = "/core-switch-tokens";
        setcookie("api-refresh", null, $cookie_options);
    }


    function getUser() {
        return $this->user;
    }


    function isExpired() {
        return $this->token_expired;
    }
}