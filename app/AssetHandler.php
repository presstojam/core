<?php
namespace PressToJamCore;

class AssetHandler {

    protected $core;



    protected function getAssetRow($alias, $data) {
        $input_shape = new Core\DataShape();
        $input_shape->addFilter("", "__id", $this->core->primary());
        $input_shape->map($data);
        $output_shape = new Core\DataShape();
        $output_shape->addField("", "asset", $this->core->getFromAlias($alias));
        $res = $this->exec("get", $input_shape, $output_shape);
        $row = $res->fetch(\PDO::FETCH_NUM);
        if (!$row) {
            throw new \Exception("Asset row doesn't exist");
        }
        return $row;
    }
}