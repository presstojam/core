<?php
namespace PressToJamCore;

class AssetManager {


    function writeAsset($model, $field, $id, $value) {

    }


    function removeAsset($model, $field, $id, $value) {

    }

    function calculate() {
        {% for field in fields %}
        {% if field.calculated %} 
                if (isset($this->data_fields["{{ field.name.snake_case }}"]) {
                    Core\Hooks::doCalculate("{{ name.snake_case }}_{{field.name.snake_case }}", $this);
                }
        {% endif %}
        {% endfor %}
            }
        
        
            function calculateAssets() {
        {% for asset in assets %}
        {% if asset.calculated %}
                if (isset($this->data_fields["{{ asset.name.snake_case }}"]) {
                    Core\Hooks::doCalculateAsset("{{ name.snake_case }}_{{ asset.name.snake_case }}", $this->export());
                }
        {% endif %}
        {% endfor %} 
            }
        
        
            function removeAssets() {
        {% for asset in assets %}
        {% if asset.calculated %}
                $this->{{ asset.name.snake_case }}->convertKeyName($this->{{ primary.snake_case }}->value);
                $this->{{ asset.name.snake_case }}->removeAsset();
        {% endif %}
        {% endfor %}        
            }
        
        
        
             
}