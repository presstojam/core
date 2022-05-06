<?php
namespace PressToJamCore;

class RolePermissions {
    protected $uri;
    protected $perms =[];

    function __construt($uri) {
        $this->uri = $uri;
    }

    function addPerm($perm) {
        $this->perms[] = $perm;
    }

    function getPerms() {
        return $this->perms;
    }

    function getURI() {
        return $this->uri;
    }
}

