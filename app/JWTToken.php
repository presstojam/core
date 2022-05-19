<?php
namespace PressToJamCore;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class JWTToken {

    private $encoding = 'HS512';
    private $key;
    private $has_expired = false;
  
    function __construct(Configs\JWT $token) {
        $this->key = $token->secret;
    }


    function encode(array $payload, int $lifetime_minutes) {
        $issuedAt   = new \DateTimeImmutable();
        $expire     = $issuedAt->modify('+' . $lifetime_minutes . ' minutes')->getTimestamp(); 
        $payload = array_merge([
            'iat'   => $issuedAt->getTimestamp(),         // Issued at: time when the token was generated
            'iss'   => $_SERVER['SERVER_NAME'],                       // Issuer
            'nbf'   => $issuedAt->getTimestamp(),         // Not before
            'exp'   => $expire],                           // Expire
           $payload);
        return JWT::encode($payload, $this->key, $this->encoding);
    }

    
    public function decode($token) {
       
        try {
            $payload = JWT::decode($token, new Key($this->key, $this->encoding));
        } catch(\Firebase\JWT\ExpiredException $e){
            $this->has_expired = true;
            return false;
        } catch (\Throwable $e) {
            return;
        }

    
        $now = new \DateTimeImmutable();
    
        if ($payload->iss !== $_SERVER['SERVER_NAME'] ||
            $payload->nbf > $now->getTimestamp() ||
            $payload->exp < $now->getTimestamp())
        {
            return;
        }

        return $payload;
    }

    public function isExpired() {
        return $this->has_expired;
    }
  
}