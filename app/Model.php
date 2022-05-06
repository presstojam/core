<?php

namespace PressToJamCore;

class Model
{
    protected $pdo;
    protected $request;
    protected $name;
    protected $user;
    protected $data;
    protected $meta;
    protected $results;
    
    function __construct($name, $pdo, $user, $request = null) {
        $this->name = $name;
        $this->pdo     = $pdo;
        $this->user = $user;
        $this->request  =  $request;
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


    function initMeta($method) {
        $this->data = new DataRow($this->request); //set up things like order, to etc.
        $this->data->mapRequestMeta($this->request);
        $meta = "\PressToJam\MetaCollections\\" . str_replace('-', '', ucwords($this->name, "-"));
        $this->meta = new $meta();
        $this->meta->addFrom($this->data);
        $this->meta->$method($this->data, $this->request);
    }


    function exec($method) {
        $this->data->mapRequestData($this->request);
        $this->data->validate();

        $stmt_builder = new StmtBuilder($this->data);
        $sql = $stmt_builder->$method();

        $stmt = new PreparedStatement($this->pdo);
        $stmt->prepare($sql);

        return $stmt->execute($this->data->toArgs());
    }


    function getResult($res) {
        $data = $res->fetch(\PDO::FETCH_NUM);
        $row = new ResultsRow($this->data, $data);
        return $row->export();
    }


    function getResults($res) {
        $results = [];
        $data = $res->fetchAll(\PDO::FETCH_NUM);
        foreach($data as $row) {
            $map = new ResultsRow($this->data, $row);
            $results[] = $map->export();
        }
        return $results;
    }



    function loadChildren($results, $meta) {
        $meta->data_fields = [];
        $meta->filter_fields = [];
        $meta->activatePrimary();
        $children = $meta->children;
               
        $collections = $meta->getAllOutputCollections();
        foreach($collections as $col) {
            $col->activate();
        }

        $stmt_builder = new StmtBuilder($meta);
        $sql =$stmt_builder->selectChildren();

        $stmt = $this->getSQL($sql);

        if (!is_array($results)) $results = [$results];

        if (is_array($results)) {
            foreach($results as $result) {
                $map = $this->createMap($meta, ["__key"=>$result->getKey()->value]);
                $res = $stmt->execute($map->toArgs());
                $data = $res->fetchAll(\PDO::FETCH_NUM);
                foreach($data as $row) {
                    $fmap = new ResultsMap();
                    $fmap->addChildren($meta->foldChildren($row));
                    $result->foldIn($fmap);
                }
            }
        }
    }

    function getSchema() {
        $fields=["data"=>[], "filter"=>[], "response"=>[]];
        foreach($this->data->data_fields as $slug=>$field) {
            $fields["data"][$slug] = $field->schema();
        }

        foreach($this->data->filter_fields as $slug=>$field) {
            $fields["filter"][$slug] = $field->schema();
        }

        foreach($this->data->response_fields as $slug=>$field) {
            $fields["response"][$slug] = $field->schema();
        }
        return $fields;
    }
}