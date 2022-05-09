<?php
namespace StorageHandler {

    function __invoke($pdo, $data_row, $method) {
        $stmt_builder = new StmtBuilder($data_row);
        $sql = $stmt_builder->$method();

        $stmt = new PreparedStatement($pdo);
        $stmt->prepare($sql);

        return $stmt->execute($data_row->toArgs());
    }
}