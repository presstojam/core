<?php

namespace PressToJamCore;


class StmtBuilder {

    protected $data_row;
    

    function __construct(DataRow $data_row) {
        $this->data_row = $data_row;
    }

    
    function buildFilter() {
        $sql = "";
        $filter_cols = [];

        foreach ($this->data_row->filter_fields as $field) {
            $filter_cols[] =  $field->mapToStmtFilter($field->alias . "." . $field->name);
        }
        if (count($filter_cols) > 0) {
            $sql .= " WHERE " . implode(" AND ", $filter_cols);
        }

        if (count($this->data_row->sort) > 0) {
            $sql .= " ORDER BY " . implode(", ", $this->data_row->sort) . " ";
        }
        

        if ($this->data_row->limit) {
            $sql .= " LIMIT " . $this->data_row->limit;
        }

        return $sql;
    }



    function get() {
        $data_cols=[];
        foreach ($this->data_row->response_fields as $field) {
            $data_cols[] = $field->alias . "." . $field->name;
        }

        foreach($this->data_row->encrypted_filter_fields as $field) {
            $data_cols[] = $field->alias . "." . $field->name;
        }
      
        if (count($data_cols) == 0) {
            throw new \Error("No cols selected for statement ");
        }
        $sql = "SELECT " . implode(",", $data_cols);
        $sql .= " FROM " . $this->data_row->from;
        $sql .= " " . implode(" ", $this->data_row->joins) . " ";
        $sql .= $this->buildFilter();
        //echo $sql;
        return $sql;
    }



    function selectChildren() {
        $data_cols = [];
        $cols = $this->data_row->children;
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
        $sql .= " FROM " . $this->data_row->from;
        $sql .= " " . implode(" ", $this->data_row->joins) . " ";
    
        $filter_cols = [];
        foreach($this-data_row->filter_fields as $field) {
            $filter_cols[] =  $field->mapToStmtFilter($field->alias . "." . $field->name);
        }
        $sql .= " WHERE " . implode(" AND ", $filter_cols);
        return $sql;
    }


    function put() {
        if (count($this->data_row->filter_fields) == 0) {
            throw new \Error("Insecure update, must have a valid where clause");
        }

        if (count($this->data_row->data_fields) == 0) {
            throw new \Error("Update error, must have cols to update");
        }

        $data_cols = [];
        foreach($this->data_row->data_fields as $field) {
            $data_cols[] = $tfield->alias . "." . $field->name . " = ?";
        }

        $sql = "UPDATE " . $this->data_row->from . " ". implode(" ", $this->joins) . " SET " . implode(",", $data_cols);

        $filter_fields = $this->data_row->filter_fields;
        foreach ($filter_fields as $field) {
            $filter_cols[] =  $field->mapToStmtFilter($field->alias . "." . $field->name);
        }

        $sql .= " WHERE " . implode(" AND ", $filter_cols);
        return $sql;
    }


    function copy() {
        if (count($this->data_row->filter_fields) == 0) {
            throw new \Error("Can't copy without a filter field");
        }

        if (count($this->data_row->data_fields) == 0) {
            throw new \Error("Copy error, must have cols to copy");
        }

        
        $data_cols = [];
        $select_cols = [];
        foreach ($this->data_row->data_fields as $field) {
            $data_cols[] = $field->name;
            $select_cols[] = $field->alias . "." . $field->name;
        }
        
        $filter_cols = [];
        foreach ($this->data_row->filter_fields as $field) {
            $filter_cols[] =  $field->mapToStmtFilter($field->alias . "." . $field->name);
        }

        $sql = "INSERT INTO " . $this->data_row->copy_table . "(" . join(", ", $data_cols) . ") SELECT ";
        $sql .= join(", ", $select_cols) . " FROM " . $this->data_row->from . " WHERE ";
        $sql .= implode(" AND ", $filter_cols);
        
        return $sql;
    }


    function post() {
        if (count($this->data_row->data_fields) == 0) {
            throw new \Error("Insert error, must have cols to update");
        }

        $cols = [];
        foreach($this->data_row->data_fields as $field) {
            $cols[$field->name] = "?";
        }

        $sql = "INSERT INTO " . $this->data_row->table . " (" . implode(", ", array_keys($cols) ) . ") ";
        $sql .= " VALUES (" . implode(", ", $cols) . ")";
        return $sql;
    }


    function delete() {

        if (count($this->data_row->filter_fields) == 0) {
            throw new \Error("Insecure delete, must have a valid where clause");
        }


        $tables = [];
        $tables[] = $this->data_row->alias;
        foreach($this->data_row->children as $child) {
            $tables[] = $child->alias;
        }

        $sql = "DELETE " . implode(", ", $tables) . " FROM " . $this->data_row->from . " ";
        $sql .= implode(" ", $this->data_row->joins);
        foreach ($this->data_row->filter_fields as $field) {
            $filter_cols[] =  $field->mapToStmtFilter($field->alias . "." . $field->name);
        }

        $sql .= " WHERE " . implode(", ", $filter_cols);
        return $sql;
    }

    function count() {
        $sql = "SELECT count(" . $this->data_row->alias . "." . $this->data_row->primary_field->name . ") AS 'count' FROM " . $this->data_row->from . " ";
        $sql .= " " . implode($this->data_row->joins) . " ";
        $sql .= $this->buildFilter();
        return $sql;
    }
  
}

