<?php
namespace PressToJamCore;

class ResultsHandler {

    private $data_row;

    function __construct($data_row) {
        $this->data_row = $data_row;
    }


    function loadHistory($row) {

    }

    function loadChildren($row) {
        
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
    

    function buildResponse($row) {
        $this->data_row->validateOutput($row);
        $results = new ResultsRow($this->data_row, $row);
        if ($this->data_row->children) {
            $this->loadHistory($results);
        }
        if ($this->data_row->history) {
            $this->loadHistory($results);
        }
        return $results;
    }

    function get($res) {
        $data = $res->fetch(\PDO::FETCH_NUM);
        return $this->buildResponse($data);
    }


    function getResults($res) {
        $results = [];
        $data = $res->fetchAll(\PDO::FETCH_NUM);
        foreach($data as $row) {
            $results[] = $this->buildResponse($row);
        }
        return $results;
    }
}