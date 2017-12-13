<?php
namespace yii\base;

use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Link;
use yii\web\Linkable;

trait ArrayableTrait
{
    public function fields()
    {
        $fields = array_keys(Yii::getObjectVars($this));
        return array_combine($fields, $fields);
    }

    public function extraFields()
    {
        return [];
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $data = [];
        foreach ($this->resolveFields($fields, $expand) as $field => $definition) {
            $data[$field] = is_string($definition) ? $this->$definition : call_user_func($definition, $this, $field);
        }

        if ($this instanceof Linkable) {
            $data['_links'] = Link::serialize($this->getLinks());
        }

        $expands = $this->expand($expand);
        return $recursive ? ArrayHelper::toArray($data, [], true, $expands) : $data;
    }

    protected function expand(array $expand) // <-- add this
    {
        $expands = [];
        foreach ($expand as $field) {
            $fields = explode('.', $field,2);
            $expands[$fields[0]][] = isset($fields[1]) ? $fields[1] : false;
        }
        foreach ($expands as $key => $value) {
            $expands[$key] = array_filter($value);
        }
        
        return $expands;
    }

    protected function resolveFields(array $fields, array $expand)
    {
        $result = [];

        foreach ($this->fields() as $field => $definition) {
            if (is_int($field)) {
                $field = $definition;
            }
            if (empty($fields) || in_array($field, $fields, true)) {
                $result[$field] = $definition;
            }
        }

        if (empty($expand)) {
            return $result;
        }

        $expands = $this->expand($expand);
        foreach ($this->extraFields() as $field => $definition) {
            if (is_int($field)) {
                $field = $definition;
            }
            if (isset($expands[$field])) {
                $result[$field] = $definition;
            }
        }

        return $result;
    }
}

