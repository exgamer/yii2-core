<?php
namespace core\models\v2;

use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use core\helpers\DbHelper;
use core\helpers\StringHelper;

/**
 * Актив рекорд с дополнительными атрибутами
 * атрибуты хранятся в другой таблице в горизонтальном виде
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
abstract class ActiveRecordWithProps extends ActiveRecord implements IARWithProperties
{      
    public static function find()
    {
        $q = parent::find();
        $q->with([static::getPropertiesRelation()]);
        $q->groupBy(static::tableName() . '.id');
        
        return $q;
    }
    
    public function __set($name, $value) 
    {
        $properties = $this->properties();
        if(in_array($name, $properties)) {
            $this->{$name} = $value;
        } else {
            parent::__set($name, $value);
        }
    }
    
    /**
     * Дополнительные атрибуты
     * 
     * @return ActiveQuery
     */
    public function getProperties()
    {
        return $this->hasMany($this->getPropertiesClass(), [$this->getLinkAttribute() => 'id']);
    }

    public function getAttributes($names = null, $except = []) 
    {
        $attributes = parent::getAttributes($names, $except);
        $properties = $this->properties();
        if(! is_array($properties)){
            return $attributes;
        }
        foreach ($properties as $property) {
            $attributes[$property] = $this->{$property};
        }
        
        return $attributes;
    }
    
    public function hasAttribute($name) 
    {
        $attributes = $this->attributes();
        $properties = $this->properties();
        $merge = ArrayHelper::merge($attributes, $properties);
        $result = array_flip($merge);
        
        return isset($result[$name]);
    }
      
    public function afterFind() 
    {
        $parent = parent::afterFind();
        $propertiesKeys = $this->properties();
        if(! $propertiesKeys) {
            return $parent;
        }
        $propertiesRelationName = static::getPropertiesRelation();
        if(empty( $this->{$propertiesRelationName}) ){
            return $parent;
        }
        foreach ($this->{$propertiesRelationName} as $propertyValue) {
            $value = null;
            if($propertyValue->value_number) {
                $value = $propertyValue->value_number;
            }
            if($propertyValue->value_string) {
                $value = $propertyValue->value_string;
            }
            if(! in_array($propertyValue->name, $propertiesKeys)) {
                continue;
            }
            if($propertyValue->index > 0){
                if(!is_array($this->{$propertyValue->name})){
                    $this->{$propertyValue->name} = [];
                }
                $this->{$propertyValue->name}[$propertyValue->index] = $value;
            } else {
                $this->{$propertyValue->name} = $value;
            }
        }
    }
    
    public function save($runValidation = true, $attributeNames = null) 
    {
        return $this->db->transaction(function($db) use($runValidation, $attributeNames){
            return parent::save($runValidation, $attributeNames);
        });
    }

    public function afterSave($insert, $changedAttributes) 
    {
        $parent = parent::afterSave($insert, $changedAttributes);
        $properties = $this->properties();
        if(! $properties) {
            return $parent;
        }
        $propertiesClass = $this->getPropertiesClass();
        $propertiesClass::deleteAll([$this->getLinkAttribute() => $this->id]);
        $insertData = [];
        foreach ($properties as $property) {
            if($this->{$property} === null) {
                continue;
            }
            if(! is_array($this->{$property})) {
                $insertData[] = $this->getInsertData($property, $this->{$property});
            } else {
                foreach ($this->{$property} as $key => $value) {
                    if($value === null) {
                        continue;
                    }
                    $insertData[] = $this->getInsertData($property, $value, $key+1);
                }
            }
        }
        if(! $insertData) {
            return $parent;
        }
        if( !DbHelper::batchInsert(new $propertiesClass(), $insertData, false)) {
            throw new Exception(Yii::t('app', 'Не удалось сохранить дополнительные атрибуты'));
        }
 
        return $parent;
    }
        
    public function fields() 
    {
        $attrs = $this->attributes;
        unset($attrs['type']);
        return array_keys($attrs);
    }
    
    /**
     * Возвращает массив для мультивставки
     * 
     * @param string $property
     * @param mixed $value
     * @param integer $index
     * @return array
     */
    private function getInsertData($property, $value, $index = 0)
    {
        $result = [
            $this->getLinkAttribute() => $this->id,
            'name' => $property,
            'index' => $index
        ];
        if (StringHelper::isDate($value)){
            $result['value_date'] = $value;
            return $result;
        }
        if (StringHelper::isJson($value)){
            $result['value_json_b'] = $value;
            return $result;
        }
        if (is_string($value)){
            $result['value_string'] = $value;
            return $result;
        }
        if (is_int($value)){
            $result['value_number'] = $value;
            return $result;
        }
        
        return $result;
    }
}