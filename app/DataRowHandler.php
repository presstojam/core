<?php
namespace PressToJamCore;

class DataRowHandler {

    private $params;
    private $data_row;
    private $meta_collection;


    function applyFields($state) {
        if ($state == "summary") {
            $fields = $this->meta_collection->getFieldsWhere("summary", true);
            foreach($fields as $slug=>$field) {
                $data_row->addResponse($this->meta_collection->alis)
            }
        }
    }

    function summary($data_row) {
        {% for property in fields %}
        {% if property.summary %}
                    $data_row->addField($this->alias, $this->slug . "{{ property.name.kebab_case }}", $this->fields["{{ property.name.kebab_case }}"]);
        {% endif %}
        {% endfor %} 
            }
                
            function get($data_row) {
                $data_row->addResponse($this->alias, $this->slug . "__id", $this->primary);
                foreach ($this->fields as $slug=>$field) {
                    if (isset($params->fields[$this->slug . $slug])) {
                        $data_row->addResponse($this->alias, $this->slug . $slug, $field);
                    }
        
                    if (isset($params->data[$this->slug . $slug])) {
                        $data_row->addFilter($this->alias, $this->slug . $slug, $field);
                    }
                }
        
                if ($this->date_created) {
                    $data_row->addResponse($this->alias, $this->slug . "date_created", $this->date_created);
                    $data_row->addResponse($this->alias, $this->slug . "last_updated", $this->last_updated);
                }
        
                
        
        
        {% if sort %}
                $data_row->sort = [ $this->alias . "." . $this->sort->name ];
        {% endif %}
            }
                 
                
            function primary($data_row) {
                $this->get($data_row);
                $data_row->addFilter($this->alias, "__id", $this->primary);
                $data_row->limit = 1;
            }
        
        {% if parent %}
            function parent($data_row) {
                $this->get($data_row);
                $data_row->addFilter($this->alias, "__parentid", $this->parent);
            }
        
        {% endif %}      
        
            function post($data_row) {
        {% if parent and (security is not defined or security.self == false) %}
                $data_row->addField($this->alias, "__parentid", $this->parent);
        {% endif %}
        {% if owner %}
                $data_row->addField($this->alias, "{{ owner.kebab_case }}", $this->owner);
        {% endif %}
        {% for col in create_fields %}
                $data_row->addField($this->alias, "{{ col.name.kebab_case }}", $this->fields["{{ col.name.kebab_case }}"]);
        {% endfor %}        
            }
        
        
                    
            function put($data_row, $params) {
        {% if parent and soft_parent %}
                if (isset($params->data["__parentid"])) {
                    $data_row->addField($this->alias, "__parentid", $this->parent);
                }
        {% endif %}
        {% for col in update_fields %}
                if (isset($params->data["{{ col.name.kebab_case }}"])) {
                    $data_row->addField($this->alias, "{{ col.name.kebab_case }}", $this->fields["{{ col.name.kebab_case }}"]);
                }
        {% endfor %}
                $data_row->addFilter($this->alias, "__id", $this->primary);
            }
        
               
                
            function delete($data_row) {
                $data_row->addFilter($this->alias, "__id", $this->primary);
                $this->addChildren();
            }
                
        
        {% if is_encrypted %}
            function login($data_row) {
                $data_row->addResponse($this->alias, "__id", $this->primary);
        {% for field in fields %}
        {% if field.encrypted %}
                $data_row->addEncryptedFilter($this->alias, "{{ field.name.kebab_case }}", $this->fields["{{ field.name.kebab_case }}"]);
        {% elseif field.unique and field.type == "str" %}
                $data_row->addFilter($this->alias, "{{ field.name.kebab_case }}", $this->fields["{{ field.name.kebab_case }}"]);
        {% endif %}
        {% endfor %}
                $data_row->limit = 1;
            }
        
            function loginview($data_row) {
        {% for field in fields %}
        {% if field.encrypted or (field.unique and field.type == "str")  %}
                $data_row->addField($this->alias, "{{ field.name.kebab_case }}", $this->fields["{{ field.name.kebab_case }}"]);
        {% endif %}
        {% endfor %}
                $data_row->limit = 1;
        
            }
        {% endif %}
        
        {% if archive %}
            function archive($data_row) {
                foreach($this->fields as $slug=>$field) {
                    $data_row->addField($this->alias, $this->slug . $slug, $field);
                }
                $data_row->addField($this->alias, "__id", $this->primary);
        {% if parent %}
                $data_row->addField($this->alias, "__parentid", $this->parent);
        {% endif %}
        {% if owner %}
                $data_row->addField($this->alias, "{{ owner.kebab_case }}", $this->owner);
        {% endif %}
        {% if sort %}
                $data_row->addField($this->alias, "{{ sort.kebab_case }}", $this->sort);
        {% endif %}
                $data_row->addFilter($this->alias, "__id", $this->primary);
            }
        
        
            function archiveView($data_row) {
                foreach($this->fields as $slug=>$field) {
                    $data_row->addField($this->alias, $this->slug . $slug, $field);
                }
                $data_row->addField($this->alias, "__id", $this->primary);
        {% if parent %}
                $data_row->addField($this->alias, "__parentid", $this->parent);
        {% endif %}
        {% if owner %}
                $data_row->addField($this->alias, "{{ owner.kebab_case }}", $this->owner);
        {% endif %}
        {% if sort %}
                $data_row->addField($this->alias, "{{ sort.kebab_case }}", $this->sort);
        {% endif %}
                $data_row->addFilter($this->alias, "{{ archive.kebab_case }}", $this->archive);
            }
        {% endif %}
                
        
        {% if sort %}
            function updateSort($data_row) {
                $data_row->addField($this->alias, "{{ sort.kebab_case }}", $this->sort);
                $data_row->addFilter($this->alias, "__id", $this->sort);
            }
        
        {% endif %}  



    function __invoke($params, $meta_name, $state) {
        $this->params = $params;
        $this->data_row = new DataRow();
        $this->meta_collection = Factory::createMeta($meta_name);


    }

    
}