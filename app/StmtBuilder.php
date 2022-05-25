<?php

namespace PressToJamCore;


class StmtBuilder {
    protected $cols = [];
    protected $structure = [];
    protected $limit;
    protected $order_by = [];
    protected $group_by = [];
    protected $having = [];
    protected $input_shape;
    protected $output_shape;
    protected $from;
    protected $from_alias = "";
    

    function __construct() {
    }

    
    function buildFilter()
    {
        $sql = "";
        $filter_cols = [];

        foreach ($this->input_shape->filter_fields as $field) {
            $filter_cols[] =  $field->mapToStmtFilter($field->alias . "." . $field->name);
        }
        if (count($filter_cols) > 0) {
            $sql .= " WHERE " . implode(" AND ", $filter_cols);
        }

    
        if (count($this->order_by) > 0) {
            $sql .= " ORDER BY " . implode(", ", $this->order_by) . " ";
        }
        

        if ($this->limit) {
            $sql .= " LIMIT " . $this->limit;
        }

        return $sql;
    }

    function joins() {
        $joins = [];
        foreach($this->input_shape->relationship_fields as $field) {
            if ($field->is_parent OR $field->is_owner) {
                $primary = $field->reference->primary();
                $join_str = "INNER JOIN " . $field->reference->table . " " . $field->reference->alias . " ON ";
                $join_str .= " " . $field->alias . "." . $field->name . " = " . $primary->alias . "." . $primary->name;
                $joins[] = $join_str;
            } else if ($field->is_primary) {
                foreach($field->reference as $ref) {
                    $parent = $ref->parent();
                    $join_str = "LEFT OUTER JOIN " . $ref->table . " " . $ref->alias . " ON ";
                    $join_str .= " " . $field->alias . "." . $field->name . " = " . $parent->alias . "." . $parent->name;
                    $joins[] = $join_str;
                }
            } else {
                if ($field->required) {
                    $join_str = "INNER JOIN ";
                } else {
                    $join_str = "LEFT OUTER JOIN ";
                }

                $primary = $field->reference->primary();
                $join_str .= $field->reference->table . " " . $field->reference->alias . " ON ";
                $join_str .= " " . $field->alias . "." . $field->name . " = " . $primary->alias . "." . $primary->name;
                $joins[] = $join_str;
            }
        }


        if ($this->output_shape) {
            foreach ($this->output_shape->relationship_fields as $field) {
                if ($field->is_primary) {
                    foreach ($field->references as $ref) {
                        $parent = $field->reference->parent();
                        $join_str = "LEFT OUTER JOIN " . $ref->table . " " . $ref->alias . " ON ";
                        $join_str .= " " . $field->alias . "." . $field->name . " = " . $parent->alias . "." . $parent->name;
                        $joins[] = $join_str;
                    }
                } else {
                    if ($field->required) {
                        $join_str = "INNER JOIN ";
                    } else {
                        $join_str = "LEFT OUTER JOIN ";
                    }
                    $primary = $field->reference->primary();
                    $join_str .= $field->reference->tabel . " " . $field->reference->alias . " ON ";
                    $join_str .= " " . $field->alias . "." . $field->name . " = " . $primary->alias . "." . $primary->name;
                    $joins[] = $join_str;
                }
            }
        }
        return implode (" ", $joins);
    }

    function get() {
        $data_cols=[];
        foreach ($this->output_shape->fields as $field) {
            $data_cols[] = $field->alias . "." . $field->name;
        }

        foreach($this->output_shape->filter_fields as $field) {
            $data_cols[] = $field->alias . "." . $field->name;
        }
      
        if (count($data_cols) == 0) {
            throw new \Error("No cols selected for statement ");
        }
        $sql = "SELECT " . implode(",", $data_cols);
        $sql .= " FROM " . $this->from . " " . $this->from_alias;
        $sql .= " " . $this->joins() . " ";
        $sql .= $this->buildFilter();
        //echo $sql;
        return $sql;
    }



    function put() {
        if (count($this->input_shape->filter_fields) == 0) {
            throw new \Error("Insecure update, must have a valid where clause");
        }

        if (count($this->input_shape->fields) == 0) {
            throw new \Error("Update error, must have cols to update");
        }

        $data_cols = [];
        foreach($this->input_shape->fields as $field) {
            $data_cols[] = $field->alias . "." . $field->name . " = ?";
        }

        $sql = "UPDATE " . $this->from . " ". $this->from_alias . " " . $this->joins() . " SET " . implode(",", $data_cols);

        $filter_fields = $this->input_shape->filter_fields;
        foreach ($filter_fields as $field) {
            $filter_cols[] =  $field->mapToStmtFilter($field->alias . "." . $field->name);
        }

        $sql .= " WHERE " . implode(" AND ", $filter_cols);
        return $sql;
    }


    function post() {
        if (count($this->input_shape->fields) == 0) {
            throw new \Error("Insert error, must have cols to update");
        }

        $cols = [];
        foreach($this->input_shape->fields as $field) {
            $cols[$field->name] = "?";
        }

        $sql = "INSERT INTO " . $this->from . " (" . implode(", ", array_keys($cols) ) . ") ";
        $sql .= " VALUES (" . implode(", ", $cols) . ")";
        return $sql;
    }


    function delete() {

        if (count($this->input_shape->filter_fields) == 0) {
            throw new \Error("Insecure delete, must have a valid where clause");
        }


        $tables = [];
        $tables[] = $this->from_alias;

        foreach($this->input_shape->relationship_fields as $field) {
            if ($field->is_primary) {
                foreach($field->reference as $ref) {
                   $tables[] = $ref->alias;
                }
            }
        }

     

        $sql = "DELETE " . implode(", ", $tables) . " FROM " . $this->from . " " . $this->from_alias . " ";
        $sql .= $this->joins();
        foreach ($this->input_shape->filter_fields as $field) {
            $filter_cols[] =  $field->mapToStmtFilter($field->alias . "." . $field->name);
        }

        $sql .= " WHERE " . implode(" AND ", $filter_cols);
        return $sql;
    }

    function count() {
        if (count($this->output_shape->fields) == 0) {
            throw new \Error("Can't count with 0 output fields");
        }

        $col = first($this->output_shape->fields);
        $sql = "SELECT COUNT (" . $col->alias . "." . $col->name . ") AS 'count' ";
        $sql .= " FROM " . $this->from . " ";
        $sql .= " " . $this->joins() . " ";
        $sql .= $this->buildFilter();
        return $sql;
    }


    function inputShape($input) {
        $this->input_shape = $input;
        return $this;
    }


    function outputShape($output) {
        $this->output_shape = $output;
        return $this;
    }


    function from($from, $alias = "") {
        $this->from = $from;
        $this->from_alias = $alias;
        return $this;
    }


    function limit($limit) {
        $this->limit = $limit;
        return $this;
    }


    function order($col, $dir = "ASC")
    {
        $this->order_by = $col . " " . $dir;
        return $this;
    }

    function having($col) {
        $this->having[] = $col;
        return $this;
    }

    function group($group) {
        $this->group[] = $group;
        return $this;
    }

    
  
}

