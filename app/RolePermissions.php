<?php
namespace PressToJamCore;

class RolePermissions {
   
    protected $perms =[];
    protected $owner_groups = [];

    function hasPermission($route, $model, $state) {
        if (!isset($this->perms[$route])) return false;
        if (!isset($this->perms[$route][$model])) return false;
        if (isset($this->perms[$route][$model][$state])) return true;
        return false;        
    }


    function hasModelPermission($route, $model) {
        return isset($this->perms[$route][$model]);     
    }


    function requiresOwner($route, $model, $state) {
        return false;
        return $this->owner_groups[$route][$model][$state];
    }



    
    function hasRoutePermission($route, $model, $state, $method) {
        if (!isset($this->perms[$route])) return false;
        if (!isset($this->perms[$route][$model])) return false;
        if (!isset($this->perms[$route][$model][$state])) return false;
        if ($this->perms[$route][$model][$state] == $method) return true;
        return false;
    }
}

