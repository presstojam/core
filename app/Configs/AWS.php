<?php

namespace PressToJamCore\Configs;

class AWS {

    public $region = "eu-west-1";
    public $version = "latest";
    public $user;
    public $pass;
    public $credentials;
    public $resource;
    public $prefix = "";
    public $cert = "";
    public $public = false;

    function toArr() {
        $arr = [
            "resource"=>$this->resource, 
            "prefix" => $this->prefix,
            "public" => $this->public,
            "settings"=> [
                "region" =>$this->region,
                "version" => $this->version]];
        if ($this->credentials) {
            $arr["settings"]["credentials"] = $this->credentials;
        } else if ($this->user) {
            $arr["settings"]["credentials"] = ["user"=>$this->user, "pass"=> $this->pass];
        }
        if ($this->cert) {
            $arr["settings"]["http"] = array("verify"=>$this->cert);
        }
        return $arr;
    }

}