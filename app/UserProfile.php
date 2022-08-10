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
                $token = WrapperFactory::createJWT();
                $payload = $token->decode($auth->getValue());
                if (!$payload OR !property_exists($payload, "u")) {
                    throw new Exceptions\UserException(403, "User not authorized, token has expired");
                } else {
                    $this->injectPayload($payload);
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


    function makePayload() {
        return [
            "u"=>$this->user, 
            "i"=>$this->id, 
            "d"=>$this->lang,
            "l"=>$this->level
        ];
    }


    function injectPayload($payload) {
        $this->user = $payload->u;
        $this->id = $payload->i;
        $this->lang = $payload->d;
        $this->level = $payload->l;
    }


    function save($response) {
        $payload = $this->makePayload();
        
        $token = WrapperFactory::createJWT();
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
        $token = WrapperFactory::createJWT();
        $payload = $token->decode($refresh->getValue());
    
        if ($payload) {
            $this->injectPayload($payload);
            $access_token = $token->encode($this->makePayload(), $this->auth_minutes);

            $cookie_expires = time() + 86400; //24 hours update
            $cookies = [];
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
        $payload = $this->makePayload();
        $payload["is_expired"] = $this->is_expired;
        return $payload;
    }

  
}