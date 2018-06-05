<?php

namespace core\models\v2\properties\table;

use Yii;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use core\helpers\DbHelper;
use core\helpers\StringHelper;

/**
 * Трейт для AR модель с дополнительными свойствами, которые хранятся в дополнительной
 * таблице в горизонтальном виде
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
trait ActiveRecordWithTablePropsTrait
{
    use \core\models\v2\properties\ActiveRecordPropsTrait;
    
    public static function find()
    {
        $q = parent::find();
        $q->with([static::getPropertiesRelation()]);
        $q->groupBy(static::tableName() . '.id');
        
        return $q;
    }
    
    public function __set($name, $value) 
    {
        $properties = static::properties();
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
        return  $this->hasMany(static::getPropertiesClass(), [
                    static::getLinkAttribute() => 'id'
                ])
                ->asArray();
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
        $properties = static::properties();
        if(! $properties) {
            return $parent;
        }
        $propertiesClass = static::getPropertiesClass();
        if(! (method_exists($propertiesClass, 'getPrimaryClass'))) {
            throw new Exception(Yii::t('app', 'Модель свойств не реализует интерфейс ARProperties'));
        }
        $primaryClass = $propertiesClass::getPrimaryClass();
        if($primaryClass != static::class) {
            throw new Exception(Yii::t('app', '$propertiesClass::getPrimaryClass() не соответсвует текущей модели.'));
        }
        $propertiesClass::deleteAll([static::getLinkAttribute() => $this->id]);
        $insertData = [];
        foreach ($properties as $property) {
            if($this->{$property} === null) {
                continue;
            }
            if(! is_array($this->{$property}) || (is_array($this->{$property}) && ArrayHelper::isAssoc($this->{$property}))) {
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
        if(! DbHelper::batchInsert(new $propertiesClass(), $insertData, false)) {
            throw new Exception(Yii::t('app', 'Не удалось сохранить дополнительные атрибуты'));
        }
 
        return $parent;
    }
          
    public function afterFind() 
    {
        $parent = parent::afterFind();
        $propertiesKeys = static::properties();
        if(! $propertiesKeys) {
            return $parent;
        }
        $propertiesRelationName = static::getPropertiesRelation();
        if(empty( $this->{$propertiesRelationName}) ){
            return $parent;
        }
        foreach ($this->{$propertiesRelationName} as $item) {
            if(! in_array($item['name'], $propertiesKeys)) {
                continue;
            }
            $this->setProperty($item);
        }
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
            static::getLinkAttribute() => $this->id,
            'name' => $property,
            'index' => $index
        ];
        if (is_int($value)){
            $result['value_number'] = $value;
            return $result;
        }
        if (is_array($value)){
            $value = json_encode($value);
        }
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
        
        return $result;
    }
    
    /**
     * Получение значения свойства
     * 
     * @param array $item
     */
    private function setProperty($item)
    {
        $value = null;
        if($item['value_number']) {
            $value = $item['value_number'];
        }
        if($item['value_string']) {
            $value = $item['value_string'];
        }
        if($item['value_date']) {
            $value = $item['value_date'];
        }
        if($item['value_json_b']) {
            $value = $item['value_json_b'];
        }
        if($item['index'] > 0){
            if(! is_array($this->{$item['name']})) {
                $this->{$item['name']} = [];
            }
            $this->{$item['name']}[$item['index']] = $value;
        } else {
            $this->{$item['name']} = $value;
        }
    }
}

