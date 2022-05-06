<?php
namespace PressToJamCore;

class RoleNav {

    protected $nav=[];


    function addNav($parent, $route_uri) {
        if (!isset($this->nav[$parent])) {
            $this->nav[$parent] = $route_uri;
        } else if (!is_array($this->nav[$parent])) {
            $this->nav[$parent] = [$this->nav[$parent]];
            $this->nav[$parent][] = $route_uri;
        }
    }

    function getNav() {
        return $this->nav;
    }
}