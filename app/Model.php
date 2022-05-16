<?php
namespace PressToJamCore;


class Model extends ShapeHandler {

    protected $pdo;
    protected $params;
    protected $hooks;
    protected $collection;
  

    function __construct($user, $pdo, $params, $hooks = null) {
        parent::__construct($user);
      
        $this->pdo = $pdo;
        $this->params = $params;
        $this->hooks = $hooks;
    }


    function exec($sql) {
        
        $stmt = new PreparedStatement($this->pdo);
        $stmt->prepare($sql);

        return $stmt->execute($this->input_shape->toArgs());
    }

    public function stmtBuilder()
    {
        $stmt_builder = new StmtBuilder();
        $stmt_builder->inputShape($this->input_shape)
            ->outputShape($this->output_shape)
            ->from($this->collections[""]->table . " " . $this->collections[""]->alias);

        return $stmt_builder;
    }

}