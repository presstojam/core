<?php

namespace PressToJamCore;

class Model
{
    protected $pdo;
    protected $request = array();
    protected $is_secure = true;
    protected $unique_checks=[];
    
    function __construct($pdo, $is_secure, $request = null) {
        $this->pdo     = $pdo;
        $this->is_secure = $is_secure;
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


    function create($meta, $maps) {
        
        $stmt_builder = new StmtBuilder($meta);
        $sql =$stmt_builder->insert();

        $stmt = $this->getSQL($sql);

        if (is_array($maps)) {
            $ids=[];
            foreach($maps as $map) {
                $stmt->execute($map->toArgs());
                $ids[] = $this->pdo->lastInsertId();
            }
        } else {
            $stmt->execute($maps->toArgs());
            return $this->pdo->lastInsertId();
        }
                
    }


    function update($meta, $maps) {

        $stmt_builder = new StmtBuilder($meta);
        $sql =$stmt_builder->update();
      
        $stmt = $this->getSQL($sql);

        if (is_array($maps)) {
            foreach($maps as $map) {
                $stmt->execute($map->toArgs());
            }
        } else {
            $stmt->execute($maps->toArgs());
        }
    }


    function fetchRow($meta, $res) {
        $data = $res->fetch(\PDO::FETCH_NUM);
        $map = new ResultsMap();
        $meta->fold($data, $map);
        return $map;
    }


    function fetchAll($meta, $res) {
        $results = [];
        $data = $res->fetchAll(\PDO::FETCH_NUM);
        foreach($data as $row) {
            $map = new ResultsMap();
            $meta->fold($row, $map);
            $results[$map->getKey()->value] = $map;
        }
        return $results;
    }


    function retrieve($meta, $maps) {

        $stmt_builder = new StmtBuilder($meta);
        $sql =$stmt_builder->select();

        $stmt = $this->getSQL($sql);

        if (is_array($maps)) {
            $results=[];
            foreach($maps as $map) {
                $res = $stmt->execute($map->toArgs());
                $results = array_merge($results, $this->fetchAll($meta, $res));
            }
            return $results;
        } else {
            $res = $stmt->execute($maps->toArgs());
            if ($meta->limit == 1) {
                return $this->fetchRow($meta, $res);
            } else {
                return $this->fetchAll($meta, $res);
            }
        }
    }


    function retrieveHistory($meta, $maps) {

        $stmt_builder = new StmtBuilder($meta);
        $sql =$stmt_builder->archive();

        $stmt = $this->getSQL($sql);

        if (is_array($maps)) {
            $results=[];
            foreach($maps as $map) {
                $res = $stmt->execute($map->toArgs());
                $results = array_merge($results, $this->fetchAll($meta, $res));
            }
            return $results;
        } else {
            $res = $stmt->execute($maps->toArgs());
            if ($meta->limit == 1) {
                return $this->fetchRow($meta, $res);
            } else {
                return $this->fetchAll($meta, $res);
            }
        }
    }


    function delete($meta, $maps) {
        if (is_array($maps)) $maps = [$maps];

        $stmt_builder = new StmtBuilder($meta);
        $sql =$stmt_builder->delete();
      
        $stmt = $this->getSQL($sql);

        foreach ($maps as $map) {
            $stmt->execute($map->toArgs());
        }
    }


    function count($meta, $map) {
        $stmt_builder = new StmtBuilder($meta);
        $sql =$stmt_builder->count();
        $stmt = $this->getSQL($sql);
        $res = $stmt->execute($map->toArgs());
        return $res->fetch(\PDO::FETCH_ASSOC);
    }


    function getSQL($sql) {
        $stmt = new PreparedStatement($this->pdo);
        //echo $sql;
        //exit;
        $stmt->prepare($sql);
        return $stmt;
    }


    function createMap($meta, $data, $include_data = false) {
        $map = new DataMap();

        $collections = $meta->getAllInputCollections();
        foreach($collections as $col) {
            $slug = $col->slug;
            $cdata = $data;
            if ($slug) {
                $cdata = (!isset($cdata[$slug])) ? [] : $cdata[$slug];
            }

            if ($slug) $slug .= "-";
            
            if ($include_data) {
                $fields = $col->data_fields;
                foreach ($fields as $fslug=>$field) {
                    $val = (isset($cdata[$fslug])) ? $cdata[$fslug] : null;
                    $map->addCell($slug . $fslug, $field, $val);
                }
            }
            
            $fields = $col->filter_fields;
            foreach ($fields as $fslug=>$field) {
                $val = (isset($cdata[$fslug])) ? $cdata[$fslug] : null;
                $map->addCell($slug . $fslug, $field, $val);
            }

            if (isset($data["__key"])) $map->setKey($data["__key"]);
        }

       $map->validate();

        foreach($this->unique_checks as $check) {
            $func = $check;
            $func($map);
        }
       
        return $map;
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

    function export($results) {
        $exports;
        if (is_array($results)) {
            $exports=[];
            foreach($results as $key=>$map) {
                $exports[$key] = $map->export();
            }
        } else {
            $exports = $results->export();
        }
        return $exports;
    }

}