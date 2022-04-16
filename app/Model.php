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

        $stmt = $this->getSQL("insert", $sql);

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
      
        $stmt = $this->getSQL("update", $sql);

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
        return $meta->fold($data);
    }


    function fetchAll($meta, $res) {
        $results = [];
        $data = $res->fetchAll(\PDO::FETCH_NUM);
        foreach($data as $row) {
            $results[] = $meta->fold($row);
        }
        return $results;
    }


    function retrieve($meta, $maps) {

        $stmt_builder = new StmtBuilder($meta);
        $sql =$stmt_builder->select();

        $stmt = $this->getSQL("select", $sql);

        if (is_array($maps)) {
            $results=[];
            foreach($maps as $map) {
                $res = $stmt->execute($map->toArgs());
                $results = array_merge($results, $this->fetchAll($meta, $map));
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
      
        $stmt = $this->getSQL("delete", $sql);

        foreach ($maps as $map) {
            $stmt->execute($map->toArgs());
        }
    }


    function count($meta, $map) {
        $stmt_builder = new StmtBuilder($meta);
        $sql =$stmt_builder->count();
        $stmt = $this->getSQL("count", $sql);
        $res = $stmt->execute($map->toArgs());
        return $res->fetch(\PDO::FETCH_ASSOC);
    }


    function getSQL($type, $sql) {
        $stmt = new PreparedStatement($this->pdo);
        //echo $sql;
        //exit;
        $stmt->prepare($sql);
        return $stmt;
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


    function createMap($meta, $data, $include_data = false) {
        $map = new DataMap();

        $collections = $meta->getAllInputCollections();
        foreach($collections as $col) {
            $slug = $col->slug;
            $cdata = $data;
            if ($slug) {
                $cdata = (!isset($cdata[$slug])) ? [] : $cdata[$slug];
            }
            if ($include_data) {
                $fields = $col->data_fields;
                foreach ($fields as $fslug=>$field) {
                    $val = (isset($cdata[$fslug])) ? $cdata[$fslug] : null;
                    $map->addCell($field, $val);
                }
            }
            
            $fields = $col->filter_fields;
            foreach ($fields as $fslug=>$field) {
                $val = (isset($cdata[$fslug])) ? $cdata[$fslug] : null;
                $map->addCell($field, $val);
            }
        }

        $map->validate();

        foreach($this->unique_checks as $check) {
            $func = $check;
            $func($map);
        }
       
        return $map;
    }


    function loadChildren($data, $meta) {
        $sql_pieces =$meta->convertToSQLChildrenPieces();
        $stmt = $this->getSQL("select", $sql_pieces);

        if (!is_array($maps)) $maps = [$maps];
        foreach ($maps as $map) {
            $stmt->execute($map->toArgs());

            $results = [];
            $data = $res->fetchAll(\PDO::FETCH_NUM);
            foreach($data as $row) {
                $results[] = $meta->fold($row);
            }
            return $results;
        }
    }

}