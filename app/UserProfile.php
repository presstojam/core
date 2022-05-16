<?php
namespace PressToJamCore;

use \Dflydev\FigCookies\FigRequestCookies;
use \Dflydev\FigCookies\FigResponseCookies;


class UserProfile implements \JsonSerializable {
    protected $user = "public";
    protected $id = 0;
    protected $role = null;
    protected $lang = null;
    private $refresh_minutes = 86400;
    private $auth_minutes = 15;
    private $required = true;


    function __construct($request)
    {
        $auth = FigRequestCookies::get($request, "api-auth");
        //otherwise check if set via cookie
        if ($auth AND $auth->getValue()) {
            $token = Configs\Factory::createJWT();
            $payload = $token->decode($auth->getValue());
            if (!$payload) {
                throw new Exceptions\UserException(401, "The access token has expired");
            } else {
                $this->user = $payload->user;
                $this->id = $payload->id;
                $this->role = $payload->role;
                $this->lang = $payload->lang;
            }
        } 
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



    function save($response) {
        $payload = [
            "user"=>$this->user, 
            "id"=>$this->id, 
            "lang"=>$this->lang, 
            "role"=>$this->role
        ];
        
        $token = Configs\Factory::createJWT();
        $access_token = $token->encode($payload, $this->auth_minutes);
        $refresh_token = $token->encode($payload, $this->refresh_minutes );
      
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


    function logout($response) {
        FigResponseCookies::remove($response, "api-auth");
        FigResponseCookies::remove($response, "api-refresh");
    }

    function jsonSerialize() {
        return [
            "user" => $this->user,
            "id" => $this->id,
            "lang" => $this->lang,
            "role" => $this->role
        ];
    }

  
}