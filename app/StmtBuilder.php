<?php

namespace PressToJamCore;


class StmtBuilder {

    protected $meta;

    protected $cols = [];
    protected $filter_cols = [];
    protected $order_cols = [];
    protected $limit = 1000;
    protected $group_cols = [];
    protected $having_cols = [];
    protected $from = "";
    protected $tables=[];
    protected $args=[];


    function __construct($meta) {
        $this->meta = $meta;
    }

    function getCols($meta) {

    }

    function getFrom($meta) {

    }

    
    function buildFilter() {
        $sql = "";
        $filter_cols = [];

        $cols = $this->meta->getAllInputCollections();
        foreach ($cols as $col) {
            $filter_fields = $col->filter_fields;
            foreach ($filter_fields as $field) {
                $filter_cols[] =  $field->mapToStmtFilter($col->alias . "." . $field->name);
            }
        }
        if (count($filter_cols) > 0) {
            $sql .= " WHERE " . implode(" AND ", $filter_cols);
        }

        if (count($this->meta->sort) > 0) {
            $sql .= " ORDER BY " . implode(", ", $this-meta->sort) . " ";
        } 

        if ($this->meta->limit) {
            $sql .= " LIMIT " . $this->meta->limit;
        }

        return $sql;
    }



    function getDataCols() {
        $data_cols = [];
        $cols = $this->meta->getAllInputCollections();
        foreach ($cols as $col) {
            $data_fields = $col->data_fields;
            foreach ($data_fields as $field) {
                $data_cols[] = $col->alias . "." . $field->name;
            }
        }
        return $data_cols;
    }



    function select() {
        $data_cols = $this->getDataCols();
        if (count($data_cols) == 0) {
            throw new \Error("No cols selected for statement ");
        }
        $sql = "SELECT " . implode(",", $data_cols);
        $sql .= " FROM " . $this->meta->table . " " . $this->meta->alias;
        $sql .= " " . $this->meta->buildJoin() . " ";
        $sql .= $this->buildFilter();
        //echo $sql;
        return $sql;
    }


    function archive() {
        $data_cols = $this->getDataCols();
        if (count($data_cols) == 0) {
            throw new \Error("No cols selected for statement ");
        }
        $sql = "SELECT " . implode(",", $data_cols);
        $sql .= " FROM " . $this->meta->table . "_archive " . $this->meta->alias . " ";
        $sql .= $this->buildFilter();
        //echo $sql;
        return $sql;
    }


    function selectChildren() {
        $data_cols = [];
        $cols = $this->meta->getAllOutputCollections();
        foreach ($cols as $col) {
            $data_fields = $col->data_fields;
            foreach ($data_fields as $field) {
                $data_cols[] = $col->alias . "." . $field->name;
            }
        }
        if (count($data_cols) == 0) {
            throw new \Error("No cols selected for statement ");
        }

        $sql = "SELECT " . implode(",", $data_cols);
        $sql .= " FROM " . $this->meta->table . " " . $this->meta->alias;
        $sql .= " " . $this->meta->buildChildrenJoins() . " ";
    
        $filter_cols = [];
        foreach($this->meta->filter_fields as $field) {
            $filter_cols[] =  $field->mapToStmtFilter($this->meta->alias . "." . $field->name);
        }
        $sql .= " WHERE " . implode(" AND ", $filter_cols);
        return $sql;
    }


    function update() {
        if (count($this->meta->filter_fields) == 0) {
            throw new \Error("Insecure update, must have a valid where clause");
        }

        if (count($this->meta->data_fields) == 0) {
            throw new \Error("Update error, must have cols to update");
        }

        $data_cols = [];
        foreach($this->meta->data_fields as $field) {
            $data_cols[] = $this->meta->alias . "." . $field->name . " = ?";
        }

        $sql = "UPDATE " . $this->meta->table . " " . $this->meta->alias . " ". $this->meta->buildJoin() . " SET " . implode(",", $data_cols);

        $filter_fields = $this->meta->filter_fields;
        foreach ($filter_fields as $field) {
            $filter_cols[] =  $field->mapToStmtFilter($this->meta->alias . "." . $field->name);
        }

        $sql .= " WHERE " . implode(" AND ", $filter_cols);
        return $sql;
    }


    function copy($to_table) {
        if (count($this->meta->filter_fields) == 0) {
            throw new \Error("Can't copy without a filter field");
        }

        if (count($this->meta->data_fields) == 0) {
            throw new \Error("Copy error, must have cols to copy");
        }

        
        $data_cols = [];
        $select_cols = [];
        foreach ($this->meta->data_fields as $field) {
            $data_cols[] = $field->name;
            $select_cols[] = $this->meta->alias . "." . $field->name;
        }
        
        $filter_cols = [];
        $cols = $this->meta->getAllInputCollections();
        foreach ($cols as $col) {
            $filter_fields = $col->filter_fields;
            foreach ($filter_fields as $field) {
                $filter_cols[] =  $field->mapToStmtFilter($col->alias . "." . $field->name);
            }
        }

        $sql = "INSERT INTO " . $to_table . "(" . join(", ", $data_cols) . ") SELECT ";
        $sql .= join(", ", $select_cols) . " FROM " . $this->meta->table . " " . $this->meta->alias . " WHERE ";
        $sql .= implode(" AND ", $filter_cols);
        
        return $sql;
    }


    function insert() {
        if (count($this->meta->data_fields) == 0) {
            throw new \Error("Insert error, must have cols to update");
        }

        $cols = [];
        foreach($this->meta->data_fields as $field) {
            $cols[$field->name] = "?";
        }

        $sql = "INSERT INTO " . $this->meta->table . " (" . implode(", ", array_keys($cols) ) . ") ";
        $sql .= " VALUES (" . implode(", ", $cols) . ")";
        return $sql;
    }


    function delete() {

        if (count($this->meta->filter_fields) == 0) {
            throw new \Error("Insecure delete, must have a valid where clause");
        }


        $tables = [];
        $tables[] = $this->meta->alias;
        $cols = $this->meta->getAllOutputCollections();
        foreach($cols as $col) {
            $tables[] = $col->alias;
        }

        $sql = "DELETE " . implode(", ", $tables) . " FROM " . $this->meta->table . " " . $this->meta->alias;
        $sql .= $this->meta->buildChildrenJoins();
        $filter_fields = $this->meta->filter_fields;
        foreach ($filter_fields as $field) {
            $filter_cols[] =  $field->mapToStmtFilter($field->name);
        }

        $sql .= " WHERE " . implode(", ", $filter_cols);
        return $sql;
    }

    function count() {
        $sql = "SELECT count(1) AS 'count' FROM " . $this->meta->table . " ";
        $sql .= $this->buildJoins();
        $sql .= $this->buildFilter();
        return $sql;
    }
  
}

