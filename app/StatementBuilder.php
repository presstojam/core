<?php

namespace PerssToJamCore;

class StatementBuilder {

    protected $data_cols=array();
	protected $from = "";
	protected $where_cols = array();
	protected $order_cols = array();
	protected $limit =-1;
	protected $offset = null;
   

    function __set($name, $val) {
		if (property_exists($this, $name)) $this->{$name} = $val;
	}

	function __get($name) {
		if (property_exists($this, $name)) return $this->{$name};
		return null;
	}


	function filterDataCols($cols=array()) {
        $cols = array_flip($cols); //switch fields to keys to intersect
		$this->output_cols=array_intersect_key($this->output_cols, $cols);
    }

    function applyFilterMask($validator) {
        $common = array_intersect(array_keys($this->filters), $filters);
        $arr=array();
        foreach($common as $key) {
            $arr[$key] = $this->filters[$key];
        }
        $this->filters = $arr;
    }

    function setLimit($limit, $offset = null) {
        $this->limit = $limit;
        $this->offset = $offset;
    }

    function addOrder($col, $dir="ASC") {
        $this->order_cols[$col] = $dir;
    }


    function mapRequestMeta($request) {
        if (isset($request['__fields'])) {
            $this->filterDataCols($request['__fields']);
        }


        if (isset($request['__order'])) {
            foreach ($request['__order'] as $key=>$val) {
                if (is_array($val)) {
                    //use the relevant reference from key
                    $dir = (strtoupper($val) == "DESC") ? "DESC" : "ASC";
                    $this->addOrder($ckey, $dir);
                } else {
                    $dir = (strtoupper($val) == "DESC") ? "DESC" : "ASC";
                    $this->addOrder($key, $dir);
                }
            }
        }


        if (isset($request["__limit"])) {
            $limit = $request["__limit"];
            if (strpos($limit, ",") === false) {
                $this->setLimit($limit);
            } else {
                $exp = explode(",", $limit);
                $this->setLimit((int) $exp[0], (int) $exp[1]);
            }
        }
    }


    function addFilter() {

    }



  
    

   
    /*
    function selectSQL() {
        $obj = $this->getQueryMaker();
        $this->sql = $obj->selectSQL();
    }

    function updateSQL() {
        $obj = $this->getQueryMaker();
        $this->sql = $obj->updateSQL();
    }


    function insertSQL() {
        $obj = $this->getQueryMaker();
        $this->sql = $obj->insertSQL();
    }

    function countSQL() {
        $obj = $this->getQueryMaker();
        $this->sql = $obj->countSQL();
    }
*/
}