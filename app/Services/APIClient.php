<?php

namespace PressToJamCore\Services;

class APIClient {

    private $http;
    private $jar;
    private $headers;
    private $debug = false;
    private $custom_headers=[];
    private $status_cbs=[];
    private $referer;

    function __construct($domain) {
        $this->jar = new \GuzzleHttp\Cookie\CookieJar;
        $this->http = new \GuzzleHttp\Client(["base_uri"=>rtrim($domain, "/"), 'cookies' => $this->jar]);
    }

    function __set($name, $value) {
        if (property_exists($this, $name)) $this->$name = $value;
    }


    function __get($name) {
        return (property_exists($this, $name)) ? $this->$name : null;
    }

    function regHeader($header, $value) {
        $this->custom_headers[$header] = $header . ": " . $value;
    }

    function regStatusCB($status, $cb) {
        $this->status_cbs[$status] = $cb;
    }

   
    private function sendCookie() {
        $this->http->put("/core/switch-tokens");
    }


    private function process($method, $url, $data = null, $body = null) {
    
        $params = [];

        $headers=array('Accept: application/json');
        $headers=[];
        $headers = array_merge($headers, $this->custom_headers);
        
        if ($this->referer) {
            $headers["REFERER"] = $this->referer;
        }

        $params["headers"] = $headers;

        if ($data) {
            if ($method == "GET") {
                $params["query"] = $data;
            } elseif ($method == "POST") {
                $params["form_params"] = $data;
            } else {
                $params["json"] = $data;
            }
        }

        if ($body) {
            $params["body"] = $body;
        }

        $r = $this->http->request($method, $url, $params);
        
        if ($r->getStatusCode() == 403) {
            $r = $this->http->put("/core/switch-tokens");
            if ($r->getStatusCode() != 200) {
                throw new \Exception("API failure for " . $url . ": " . $r->getStatusCode() . " " . $r->getReasonPhrase());
            }
            $r = $this->http->request($method, $url, $params);
            if ($r->getStatusCode() != 200) {
                throw new \Exception("API failure for " . $url . ": " . $r->getStatusCode() . " " . $r->getReasonPhrase());
            }
        } else if ($r->getStatusCode() == 401) {
            throw new \Exception("API failure for " . $url . ": 401 Authentication failed");
        } else if ($r->getStatusCode() != 200) {
            throw new \Exception("API failure for " . $url . ": " . $r->getStatusCode() . " " . $r->getReasonPhrase());
        }
 
        $body = $r->getBody();

        $content_type = $r->getHeader("Content-Type");

        if (strpos($content_type[0], "json") !== false) {
            $json = json_decode($body, true);
            if ($json === null) {
                switch (json_last_error()) {
                case JSON_ERROR_DEPTH:
                    throw new \Exception("API JSON failure for " . $url . ": Maximum stack depth exceeded\n\n" . $body);
                break;
                case JSON_ERROR_CTRL_CHAR:
                    throw new \Exception("API JSON failure for " . $url . ": Unexpected control character found\n\n" . $body);
                break;
                case JSON_ERROR_SYNTAX:
                    throw new \Exception("API JSON failure for " . $url . ": Syntax error, malformed JSON\n\n" . $body);
                break;
            }
            }
            return $json;
        } else {
            return (string) $body;
        }
    }



    public function get($url, $data=null)
    {
        return $this->process("GET", $url, $data);
    }


    public function post($url, $data = [])
    {
        return $this->process("POST", $url, $data);
    }

    public function put($url, $data)
    {
        return $this->process("PUT", $url, $data);
    }

    public function delete($url, $data)
    {
        return $this->process("DELETE", $url, $data);
    }


    public function pushAsset($url, $blob) {
        return $this->process("PATCH", $url, null, $blob);
    }
}
