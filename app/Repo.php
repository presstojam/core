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

    function getParentChain($to)
    {
        $parents = [];
        $meta = $this->collections[""];
        while ($meta->hasParent()) {
            $parent = $meta->parent();
            $meta = $parent->reference;
            $parents[$meta->model] = $parent->slug;
            if ($meta->model == $to) {
                break;
            }
        }
        return $parents;
    }

    public function getChildren($results) {
        $index = new ResultsIndex($this->collections[""]->model, $results);
        $nparams = new Params();
    
        $model = $this->collections[""]->model;
        
        $nparams->data = [$model . "/--id"=>1];
        $nparams->to = $model;
        $nparams->fields = ["*"];

        $ids = [];
     
        $cols = $index->getCollection($model);
        foreach($cols as $col) {
            $ids[] = $col->{"--id"};
        }
        

        foreach($this->params->children as $child) {
            $repo = Factory::createRepo($child, $this->user, $this->pdo, $nparams);
            $parents = $repo->getParentChain($model);
            $repo->getQuery();
            $repo->input_shape->map([$model . "/--id"=>1]);
            $stmt_builder = $repo->stmtBuilder();
            $stmt = new PreparedStatement($this->pdo);
            $sql = $stmt_builder->get();
            $stmt->prepare($sql);
            foreach($ids as $id) {
                $repo->input_shape->map([$model . "/--id"=>$id]);
                $res = $stmt->execute($repo->input_shape->toArgs());
                $results = $repo->getResults($res);
                //check if history and load in if that is the case
                $index->append($child, $results, $parents);
            }
        }
        return $index->getCollection($model);
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

        foreach($this->collections as $slug=>$col) {
            if (!isset($fields[$slug])) $fields[$slug] = [];
            $fields[$slug][] = "--id";
        }
        
        $this->setFields($this->output_shape, $fields);
        $this->setFilterFields($this->params->data);
    }


    public function getSlugTrail() {
        $this->setStructure($this->collections[""], $this->params->to);
        $fields = [];
        $owner = false;
        foreach($this->collections as $slug=>$col) {
            if ($col->hasParent()) {
                $fields[$slug] = ["--parentid"];
            } else {
                $owner = trim($slug, "/");
            }
        }
        
        if (count($fields) == 0) {
            return ($owner) ? [["model"=>$owner]] : [];
        }
        $this->setFields($this->output_shape, $fields);

        $this->setFilterFields($this->params->data);
        $this->input_shape->map($this->params->data);

        $stmt_builder = $this->stmtBuilder()
            ->limit(1);

        $res = $this->exec($stmt_builder->get());
        $data = $res->fetch(\PDO::FETCH_NUM);
      
        $result = $this->getResult($data);
        $trails = [];
        $fields = $this->output_shape->fields;
        foreach($this->collections as $slug=>$col) {
            //need model slug plus id
            $trail = ["model"=>$col->model];
            if ($col->hasParent()) $trail["id"] = $result->{$slug . "--parentid"};
            $trails[] = $trail;
        }

        return array_reverse($trails);
    }



    public function getCount($secure) {
        $this->params->limit = "";
        $this->setStructure($this->collections[""], $this->params->to);
        $cell = $this->createCell($this->collections[""], "--id");
        $cell->func = "COUNT";
        $this->output_shape->addField($cell->slug, $cell);

        $this->setFilterFields($this->params->data);
        $this->input_shape->map($this->params->data);

        $stmt_builder = $this->stmtBuilder();
        $res = $this->exec($stmt_builder->get());
        $data = $res->fetch(\PDO::FETCH_NUM);
        if (!$data) return ["count"=>0];
        else return ["count"=>$data[0]];
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

}