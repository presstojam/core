<?php
namespace PressToJamCore;


class PreparedStatement {

    protected $stmt;
    protected $pieces;
    protected $pdo;
    protected $sql;
   


    function __construct($pdo, StmtPieces $pieces = null) {
        $this->pdo = $pdo;
        $this->pieces = $pieces;
    }


	function __set($name, $val) {
		if (property_exists($this, $name)) $this->{$name} = $val;
	}

	function __get($name) {
		if (property_exists($this, $name)) return $this->{$name};
        else echo "\n" . $name . " does not exist";
		return null;
	}


    
    function prepare($sql)
    {
        $this->sql = $sql;
        try {
            $this->stmt = $this->pdo->prepare($sql);
        } catch(\PDOException $e) {
            throw new \Exception($sql . " \n\n " . $e->getMessage());
        }
	}

 
    function execute($args=[]) {
        try {
            $this->stmt->execute($args);
        } catch(\PDOException $e) {
            throw new Exceptions\SQLException($this->sql, $args, $e->getMessage());
        }
        return $this->stmt;
    }


    function buildOrder() {
        if (count($this->pieces->order_cols) > 0) {
            return " ORDER BY " . implode(", ", $this->pieces->order_cols) . " ";
        } else {
            return "";
        }
    }


    function buildLimit() {
        if ($this->pieces->limit) {
            return " LIMIT " . $this->pieces->limit;
        } else {
            return "";
        }
    }

    function buildWhere() {
        $cols = [];
        foreach($this->pieces->filter_cols as $part) {
            $cols[] = $part[0] . " " . $part[1] . " ?";
        }
        if (count($cols) > 0) {
            return " WHERE " . implode(" AND ", $cols) . " ";
        }
    }


    function buildFrom() {
        $sql = $this->pieces->from;
        $sql .= " " . $this->pieces->join . " ";
        return $sql;
    }



    function select() {
      
        if (count($this->pieces->cols) == 0) {
            throw new \Error("No cols selected for statement ");
        }
        $sql = "SELECT " . implode(",", $this->pieces->cols);
        $sql .= " FROM " . $this->buildFrom();
        $sql .= $this->buildWhere();
        $sql .= $this->buildOrder();
        $sql .= $this->buildLimit();
        return $sql;
       // echo " SQL IS " . $sql;
    }

    function update() {
        if (count($this->pieces->filter_cols) == 0) {
            throw new \Error("Insecure update, must have a valid where clause");
        }

        if (count($this->pieces->cols) == 0) {
            throw new \Error("Update error, must have cols to update");
        }

        $data_cols = [];
        foreach($this->pieces->cols as $name) {
            $data_cols[] = $name . " = ?";
        }

        $sql = "UPDATE " . $this->buildFrom() . " SET " . implode(",", $data_cols);
        $where = $this->buildWhere();
        if (!$where) {
            throw new Error("Can't delete without a where clause");
        }
        $sql .= $where;
        $sql .= $this->buildOrder();
        $sql .= $this->buildLimit();
        //echo " SQL IS " . $sql;
        return $sql;
    }


    function insert() {
        if (count($this->pieces->cols) == 0) {
            throw new \Error("Insert error, must have cols to update");
        }

        $data_cols = [];
        foreach($this->pieces->cols as $name) {
            $data_cols[$name] = "?";
        }

        $sql = "INSERT INTO " . $this->pieces->from . " (" . implode(", ", array_keys($data_cols) ) . ") ";
        $sql .= " VALUES (" . implode(", ", $data_cols) . ")";
        return $sql;
    }


    function delete() {
        $sql = "DELETE " . implode(", ", $this->pieces->tables) . " FROM " . $this->buildFrom();
        $where = $this->buildWhere();
        if (!$where) {
            throw new Error("Can't delete without a where clause");
        }
        $sql .= $where;
        $sql .= $this->buildOrder();
        $sql .= $this->buildLimit();
        return $sql;
    }

    function count() {
        $sql = "SELECT count(1) AS 'count' FROM " . $this->buildFrom();
        $sql .= $this->buildWhere();
        $sql .= $this->buildLimit();
        return $sql;
    }

}