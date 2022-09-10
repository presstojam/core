<?php
namespace PressToJamCore;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ModelController {

    protected $name;
    protected $profile;

    function __construct() {
        $this->profile = $container->get("profile");
    }
      
    function setModel($name) {
        $this->name = $name;
    }

    private function loadSchema($slug = "") {
        return new $schema($slug);
    }


    function create(Request $request, Response $response) {
        if (!$this->profile->hasPermission($this->name, "post")) {
            throw Exception();
        }

        $schema = new $this->name();
        $validator = new Validator();
        $model = new Model();

        if ($schema->hasParent()) {
            $validator->_parent = $schema->get("--parent");
        }

        $aliases = $schema->getSkeletonCreate();
        foreach($aliases as $alias) {
            $validator->{ Str::camel($alias) } = $schema->get($alias);
        }


        $data = $request->all();
        foreach($data as $key=>$val) {
            if (!$schema->has($key)) {
                throw new ();
            }

            $name = Str::camel($key);
            //set any additional schema columns
            if (!$validator->has($name)) {
                $validator->{ $name } = $schema->get($key);
            }
            $model->{ $name } = $val;
        }


        if (!$validator->validate($model)) {
            throw $validate->getErrors();
        }

        $id = $model->create();
        trigger($this->name, "create", $model->asCollection());
        $response->body->write(json_encode(["--id", $id]));

    }


    function update(Request $request, Response $response) {
        if (!$this->profile->hasPermission($this->name, "put")) {
            throw Exception();
        }

        $schema = new $this->name();
        $validator = new Validator();
        $model = new Model();

        $validator->_id = $schema->get("--id");

        $data = $request->all();
        $model->_id = $data["--id"];

        $original_data = $model->get();

        foreach($data as $key=>$val) {
            if ($key == "--id") continue;
            $name = Str::camel($key);
            //set any additional schema columns
            if (!$validator->has($name)) {
                $validator->{ $name } = $schema->get($key);
            }
            $model->{ $name } = $val;
        }

        if (!$validator->validate($model)) {
            throw $validate->getErrors();
        }

        if ($schema->archive) {
            //archive data first
        }
        $model->update();

        trigger($this->name, "update", $model->asCollection(), $original_data);

        $response->body->write(json_encode("success"));
    }


    function delete(Request $request, Response $response) {
        if (!$this->profile->hasPermission($this->name, "delete")) {
            throw Exception();
        }

        $schema = new $this->name();
        $validator = new Validator();
        $model = new Model();

        $validator->_id = $schema->get("--id");
        $data = $request->all();
        $model->_id = $data["--id"];

        $data = $model->get();

        if (!$validator->validate($model)) {
            throw $validate->getErrors();
        }

        if ($schema->archive) {
            //archive data first
        }

        $model->delete();

        trigger($this->name, "delete", $model->asCollection(), $original_data);
        $response->body->write(json_encode("success"));
    }


    function get(Request $request, Response $response) {
        if (!$this->profile->hasPermission($this->name, "get")) {
            throw Exception();
        }

        $schema = new $this->name();
        $validator = new Validator();
        $model = new Model();

        foreach($data as $key=>$val) {
            if (strpos($key, "__") === 0) {
                $this->parseIncomingMetaData($model, $key, $val);
            } else {
                $name = Str::camel($key);
                //set any additional schema columns
                if (!$validator->has($name)) {
                    $validator->{ $name } = $schema->get($key);
                }
                $model->{ $name } = $val;
            }
        }

        $model->setLimit(1);

        if (!$validator->validate()) {
            throw $validate->getErrors();
        }

        $collection = $model->get();
        trigger($this->name, "select", $collection);
        $response->body->write(json_encode($collection));
    }


    function getAll(Request $request, Response $response) {
        if (!$this->profile->hasPermission($this->name, "get")) {
            throw Exception();
        }

        $schema = new $this->name();
        $validator = new Validator();
        $model = new Model();

        foreach($data as $key=>$val) {
            if (strpos($key, "__") === 0) {
                $this->parseIncomingMetaData($model, $key, $val);
            } else {
                $name = Str::camel($key);
                //set any additional schema columns
                if (!$validator->has($name)) {
                    $validator->{ $name } = $schema->get($key);
                }
                $model->{ $name } = $val;
            }
        }

        if (!$validator->validate()) {
            throw $validate->getErrors();
        }

        $collections = $model->get();
        trigger($this->name, "select", &$collections);
        $response->body->write(json_encode($collections));
    }


    function resort(Request $request, Response $response) {
        if (!$this->profile->hasPermission($this->name, "put")) {
            throw Exception();
        }

        $schema = new $this->name();
        $validator = new Validator();
        $model = new Model();

        $validator->_id = $schema->get("--id");
        $validator->_sort = $schema->get("--sort");

        $mapper = new Mapper();
        $mapper->field(["--id"])->where("--sort");
        $mapper->update();

        $data = $request->collect("_rows");
        foreach($data as $id=>$sort) {
            $model->_id = $id;
            $model->_sort = $sort;
            if (!$validator->validate($model)) {
                throw $validate->getErrors();
            }
            
            $mapper->execute($model);
        }

        $response->body->write(json_encode("success"));
    }


    function count(Request $request, Response $response) {
        if (!$this->profile->hasPermission($this->name, "get")) {
            throw Exception();
        }

        $schema = new $this->name();
        $validator = new Validator();
        $model = new Model();

        foreach($data as $key=>$val) {
            if (strpos($key, "__") === 0) {
                $this->parseIncomingMetaData($model, $key, $val);
            } else {
                $name = Str::camel($key);
                //set any additional schema columns
                if (!$validator->has($name)) {
                    $validator->{ $name } = $schema->get($key);
                }
                $model->{ $name } = $val;
            }
        }

        if (!$validator->validate()) {
            throw $validate->getErrors();
        }

        $mapper = new Mapper();
        $mapper->field(["--id"])->where($model);
        $collection = $mapper->get();

        $response->body->write(json_encode(["count"=>$collection->count]));
    }


    private function parseIncomingMetaData($model, $key, $val) {
        if ($key == "__to") $model->setTo($val);
        else if ($key == "__fields") $model->setSelect($val);
        else if ($key == "__children") $model->setChildren($val);
        else if ($key == "__group") $model->setGroup($val);
        else if ($key == "__order") $model->setOrder($val);
        else if ($key == "__limit") $model->setLimit($val);
    }

}