<?php

namespace core\models\v2\properties;

use yii\db\Expression;
use core\models\v2\properties\column\IARWithColumnProps;
use core\models\v2\properties\table\IARWithTableProps;

/**
 * Трейт для моделей с дополнительными свойствами
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
trait ActiveQueryPropsTrait
{   
    /**
     * Раскладывает дополнительные атрибуты в основной массив если asArray
     */
    protected function setModelProperties()
    {
//        if(! $this->asArray) {
//            return;
//        }
        if(! method_exists($this, 'getModelInstance')) {
            return;
        }

        $model = $this->getModelInstance();
        if(
                (! $model instanceof IARWithColumnProps) 
                && (! $model instanceof IARWithTableProps)
        ) {
            return;
        }
        $properties = $model->properties();
        if(! $properties || ! is_array($properties)) {
            return;
        }
        list(, $alias) = $this->getTableNameAndAlias();
        $this->select = ["{$alias}.*"];
        
        if($model instanceof IARWithColumnProps) {
            $this->setColumnProperties( $model::propertiesColumn(), $properties );
        }
        if($model instanceof IARWithTableProps) {
            
        }
    }
    
    /**
     * Устанавливает атрибуты для подхода через колонку атрибутов
     * 
     * @param string $column
     * @param array $properties
     */
    protected function setColumnProperties($column, $properties)
    {
        foreach ($properties as $property) {
            $string = new Expression("{$column} ->>'{$property}' as {$property}");
            $this->addSelect($string);
        }
    }
    
    /**
     * @todo реализовать
     * @param array $properties
     */
    protected function setTableProperties($properties)
    {

    }
    
    /**
     * Returns the table name and the table alias for [[modelClass]].
     * @return array the table name and the table alias.
     * @internal
     */
    private function getTableNameAndAlias()
    {
        if (empty($this->from)) {
            $tableName = $this->getPrimaryTableName();
        } else {
            $tableName = '';
            foreach ($this->from as $alias => $tableName) {
                if (is_string($alias)) {
                    return [$tableName, $alias];
                }
                break;
            }
        }

        if (preg_match('/^(.*?)\s+({{\w+}}|\w+)$/', $tableName, $matches)) {
            $alias = $matches[2];
        } else {
            $alias = $tableName;
        }

        return [$tableName, $alias];
    }
}