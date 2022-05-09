<?php
namespace PressToJamCore;


class PreparedStatement {

    protected $stmt;
    protected $pdo;
    protected $sql;
   


    function __construct($pdo) {
        $this->pdo = $pdo;
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
            if ($_ENV['DEV']) {
                throw new Exceptions\SQLException($this->sql, $args, $e->getMessage());
            } else {
                throw new Exception("SQL Error");
            }
        }
	}

 
    function execute($args=[]) {
        try {
            $this->stmt->execute($args);
        } catch(\PDOException $e) {
            if ($_ENV['DEV']) {
                throw new Exceptions\SQLException($this->sql, $args, $e->getMessage());
            } else {
                throw new Exception("SQL Error");
            }
        }
        return $this->stmt;
    }


   

}