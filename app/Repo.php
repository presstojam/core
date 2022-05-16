<?php
namespace PressToJamCore;


class Repo extends Model
{
    
    public function __construct($user, $pdo, $params, $hooks)
    {
        parent::__construct($user, $pdo, $params, $hooks);
    }

    public function loadHistory($row)
    {
    }

    public function getQuery() {
        $this->setStructure($this->collections[""], $this->params->to);
        $fields = [];
        if (count($this->params->fields) > 0) {
            foreach($this->params->fields as $field) {
                $slug = $this->getCollectionName($field);
                if (!isset($fields[$slug])) $fields[$slug] = [];
                $name = $this->getFieldName($field);
                if ($name == "*summary" OR $name == "*" OR $name == "*none") $fields[$slug][] = $name;
                else $fields[$slug][] = $this->getFieldName($field);
            }
        } else {
            foreach($this->collections as $slug=>$col) {
                if (!isset($fields[$slug])) $fields[$slug] = [];
                $fields[$slug][] = (!$slug) ? "*" : "*summary";
            }
        }
        
        $this->setFields($this->output_shape, $fields);

        $this->setFilterFields($this->params->data);
        $this->input_shape->map($this->params->data);
    }

    public function getCount($secure) {
        $this->setStructure($this->collection, $this->params->to);
        $cell = $this->createCell($this->collection, "__id");
        $cell->func = "COUNT";
        $this->output_shape->addField($cell->slug, $cell);

        $this->setFilterFields($this->params->data);
        $this->input_shape->map($this->params->data);


        $stmt_builder = $this->stmtBuilder();
        $res = $this->exec($stmt_builder->get(), $output_shape);
        $data = $res->fetch(\PDO::FETCH_NUM);
        return $data;
    }


    public function stmtBuilder()
    {
        $stmt_builder = parent::stmtBuilder();
        if ($this->params->sort) {
            foreach ($this->params->sort as $val) {
                $stmt_builder->orderBy($val);
            }
        }

        if ($this->params->limit) {
            $stmt_builder->limit($this->params->limit);
        }
        return $stmt_builder;
    }


    public function getResult($row)
    {
        if (!$row) {
            throw new Exceptions\PtjException("No database results found");
        }
        $results = new ResultsRow($this->output_shape, $row);
        $results->validate();
        $results->calculate();
        if ($this->output_shape->children) {
            $this->loadHistory($results);
        }
        if ($this->output_shape->history) {
            $this->loadHistory($results);
        }
        return $results;
    }

 

    public function getResults($res)
    {
        $results = [];
        $data = $res->fetchAll(\PDO::FETCH_NUM);
        foreach ($data as $row) {
            $results[] = $this->getResult($row);
        }
        return $results;
    }


    function slug() {
        $this->getQuery();
        $stmt_builder = $this->stmtBuilder()
            ->limit(1);

        $res = $this->exec($stmt_builder->get());
        $data = $res->fetch(\PDO::FETCH_NUM);
        $row = $this->getResult($data);
        $arr = [];
        foreach ($cells as $cell_name=>$cell) {
            $slug = $this->getCollectionName($cell_name);
            if (!isset($arr[$slug])) {
                $arr[$slug] = ["__id"=>0, "values"=>[], "model"=>$slug];
            }
            $name = $this->getFieldName($cell_name);
            if ($name == "__id") {
                $arr[$slug]["__id"] = $cell->export();
            } else {
                $arr[$slug]["values"][] = $cell->export();
            }
        }
        return array_values($arr);
    }
}