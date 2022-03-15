<?php

namespace PressToJamCore;

class Model
{
    protected $pdo;
    protected $request = array();
    protected $is_secure = true;
    protected $rels;
    protected $original_data = [];
    protected $response;
  

    function __construct($pdo, $is_secure, $request = null) {
        $this->pdo     = $pdo;
        $this->is_secure = $is_secure;
        $this->request  =  $request;
        $this->response = new Responses\Response();
        
    }

    function __get($name) {
        if (property_exists($this, $name)) return $this->$name;
        else return null;
    }
    
    function setPDO($pdo) {
        $this->pdo = $pdo;
    }

    function setRequest($request) {
        $this->request = $request;
    }

    function init() {
        $this->rels->init($this->request);
        $this->rels->mapAliases($this->request);
    }

    function setData() {
        $this->rels->applyData($this->request->data);
        $errors = $this->rels->validate();
        if (count($errors) > 0) {
            throw new Exceptions\ValidationException($errors);
        }
        $this->rels->calculate();
    }

    function getSQL($type, $pieces) {
        $stmt = new PreparedStatement($this->pdo, $pieces);
        $sql = $stmt->$type();

        if ($this->request->debug) {
            $this->response->setDebugData(["sql"=>$sql, "args"=>$this->rels->getArgs()]);
        }
        $stmt->prepare($sql);
        if ($this->request->sets) {
            $arr=[];
            for($i=0; $i<$this->request->sets; ++$i) {
                $arr[] = $stmt->execute($this->rels->getArgsByRow($i));
            }
            return $arr;
        } else {
            return $stmt->execute($this->rels->getArgs());
        }
    }
  
    function setResults() {
        $root = $this->rels->getRoot();
        $this->response->setData($root->export($this->request->raw));
        return $this->response;
    }

    function loadArchive($tree) {
        $archives = $this->rels->getActiveArchives();
        $request = new Request([]);
        foreach($archives as $class_name) {
            $ns = "\PressToJam\Models\\" . $class_name;
            $archive = new $ns($this->pdo, $request);
            $archive->addToTree($tree);
        }
    }

}