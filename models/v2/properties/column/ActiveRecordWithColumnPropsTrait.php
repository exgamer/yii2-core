<?php

namespace core\models\v2\properties\column;

use \yii\helpers\Json;
use \core\helpers\StringHelper;
use \core\interfaces\IBaseSearch;

/**
 * Трейт для AR модель с дополнительными свойствами, которые хранятся в дополнительной
 * колонке в формате JSON (колонка jsonb)
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
trait ActiveRecordWithColumnPropsTrait
{
    use \core\models\v2\properties\ActiveRecordPropsTrait;
    
    public static function propertiesColumn()
    {
        return 'properties';
    }

    public static function properties()
    {
        return [];
    }
    
    public function __get($name)
    {
        if (in_array($name, static::properties())) {
            return $this->getProperty($name);
        }

        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        if (in_array($name, static::properties())) {
            if($this instanceof IBaseSearch) {
                $this->{$name} = $value;
            } else {
                $this->setProperty($name, $value);
            }
        } else {
            parent::__set($name, $value);
        }
    }

    public function fields() 
    {
        $f = parent::fields();
        $column = $this->propertiesColumn();
        unset($f[$column]);
        return array_merge(
            $f,
            $this->properties()
        );
    }
    
    /**
     * @inheritdoc
     */
    public static function populateRecord($record, $row)
   {
       $propertiesColumn = static::propertiesColumn();
       if (isset($row[$propertiesColumn]) && StringHelper::isJson($row[$propertiesColumn])) {
           $record->{$propertiesColumn} = Json::decode($row[$propertiesColumn]);
           unset($row[$propertiesColumn]);
       }

       parent::populateRecord($record, $row);
   }
     
    /**
     * Получение значения свойства
     * 
     * @param string $name
     */
    private function getProperty($name)
    {
        $column = static::propertiesColumn();
        $items = $this->{$column};
        
        return isset($items[$name]) ? $items[$name] : null;
    }

    /**
     * Установка свойства
     * 
     * @param string $name
     * @param mixed $value
     */
    private function setProperty($name, $value)
    {
        $column = static::propertiesColumn();
        $items = $this->{$column};
        if(! $items) {
            $items = [];
        } 
        $items[$name] = $value;
        $this->{$column} = $items;
    }
}