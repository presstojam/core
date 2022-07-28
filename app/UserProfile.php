<?php
namespace PressToJamCore;

use \Dflydev\FigCookies\FigRequestCookies;
use \Dflydev\FigCookies\FigResponseCookies;


class UserProfile implements \JsonSerializable {
    protected $user = "public";
    protected $id = 0;
    protected $lang = "";
    protected $level = 0;
    private $refresh_minutes = 86400;
    private $auth_minutes = 15;
    private $is_owner = false;
    private $is_expired = false;


    function __construct($request = null)
    {
        if ($request) {
            $auth = FigRequestCookies::get($request, "api-auth");
            //otherwise check if set via cookie
            if ($auth and $auth->getValue()) {
                $token = Configs\Factory::createJWT();
                $payload = $token->decode($auth->getValue());
                if (!$payload) {
                    $this->is_expired = true;
                } else {
                    $this->user = $payload->u;
                    $this->id = $payload->i;
                    $this->lang = $payload->d;
                    $this->level = $payload->l;
                }
            }
        }
    }


    function __set($key, $value) {
        if (property_exists($this, $key)) $this->$key = $value;
    }

    function __get($key) {
        if (property_exists($this, $key)) return $this->$key;
    }


    function createCookie($name, $value, $expires) {
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
            "u"=>$this->user, 
            "i"=>$this->id, 
            "d"=>$this->lang,
            "l"=>$this->level
        ];
        
        $token = Configs\Factory::createJWT();
        $access_token = $token->encode($payload, $this->auth_minutes);
        $refresh_token = $token->encode($payload, $this->refresh_minutes );

        $cookie_expires = time() + 86400; //24 hours update

        $cookies = [];
        $cookies[] = $this->createCookie("api-auth", $access_token, $cookie_expires);
        $cookies[] = $this->createCookie("api-refresh", $refresh_token, $cookie_expires);

        $set = new \Dflydev\FigCookies\SetCookies($cookies);
        $response = $set->renderIntoSetCookieHeader($response);
      
        return $response;
    }


    function switchTokens($request, $response) {
        $refresh = FigRequestCookies::get($request, "api-refresh");
        $auth = FigRequestCookies::get($request, "api-auth");
        $token = Configs\Factory::createJWT();
        $payload = $token->decode($refresh->getValue());
        $payload = json_decode(json_encode($payload), true);
        if ($payload) {
            $access_token = $token->encode($payload, $this->auth_minutes);

            $cookie_expires = time() + 86400; //24 hours update
            $cookies[] = $this->createCookie("api-auth", $access_token, $cookie_expires);

            $set = new \Dflydev\FigCookies\SetCookies($cookies);
            $response = $set->renderIntoSetCookieHeader($response);
            return $response;
        } else {
            //$response = $this->logout($response);
            throw new Exceptions\UserException(401, "User not authenticated");
        }
    }


    function logout($response) {
       
        $cookie_expires = 0;
        $cookies = [];
        $cookies[] = $this->createCookie("api-auth", "", $cookie_expires);
        $cookies[] = $this->createCookie("api-refresh", "", $cookie_expires);

        $set = new \Dflydev\FigCookies\SetCookies($cookies);
        $response = $set->renderIntoSetCookieHeader($response);
      
        return $response;
    }

    function jsonSerialize() : mixed {
        return [
            "user" => $this->user,
            "id" => $this->id,
            "lang" => $this->lang,
            "is_expired" => $this->is_expired
        ];
    }

  
}