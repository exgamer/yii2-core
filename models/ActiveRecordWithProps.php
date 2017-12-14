<?php
namespace core\models;

use Yii;
use core\models\ActiveRecord;
use yii\base\Exception;
use core\helpers\DbHelper;

/**
 * Класс содержащий методы для работы с моделями с properties
 * 
 * ВНИМАНИЕ!!!
 * Класс не должен содержать свойств public которые не должны быть properties
 * 
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class ActiveRecordWithProps extends ActiveRecord 
{
    public static function find()
    {
        $query = parent::find();
        $query->with(['properties']);
        
        return $query;
    }
    
    public function populateRelation($name, $records)
    {
        if($name == 'properties'){
            foreach($records as $r){
                $this->{$r->name}=$r->value;
            }
        }else{
            parent::populateRelation($name, $records);
        }
    }

    /**
     *  save base model with all properties
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        return self::getDb()->transaction(function($db) use($runValidation, $attributeNames){
            if (! parent::save($runValidation, $attributeNames)){
                throw new Exception(Yii::t('api','Ошибка сохранения, основной модели'), 500);
            }
            $this->saveProps();
            $this->afterSaveProps();

            return true;
        });
    }
    
    /**
     *  saves properties
     */
    public function saveProps()
    {
        $props = get_object_vars($this);
        $model = $this->getPropertyModel();
        $model::deleteAll(['id_object' => $this->id]);
        $data = [];
        foreach ($props as $name=>$value) {
            if (! $value){
                continue;
            }
            if (is_array($value)){
                $value = json_encode($value);
            }
            $data[] = [
                'id_object' => $this->id,
                'name' => $name,
                'value' => (string)$value,
            ];
        }
        if (! DbHelper::batchInsert($model, $data)){
            throw new Exception(Yii::t('api', 'Не удалось сохранить property.'));
        }
    }
    
    /**
     * actions after props save
     */
    public function afterSaveProps()
    {
        
    }

    public function delete()
    {
        return self::getDb()->transaction(function($db) {
            $this->deleteProps();
            if (! parent::delete()){
                throw new Exception(Yii::t('api','Ошибка удаления, основной модели'), 500);
            }
            $this->afterDeleteProps();

            return true;
        });
    }
    
    /**
     *  deletes properties
     */
    public function deleteProps()
    {
       $model::deleteAll(['id_object' => $this->id]);
    }
    
    /**
     * actions after props delete
     */
    public function afterDeleteProps()
    {
        
    }
    
    /**
     * Метод наполучение модели со свойствами
     */
    abstract function getPropertyModel();

    /**
     * Получить свзяь со свойствами
     * @return ActiveQuery
     */
    public function getProperties()
    {
        return $this->hasMany($this->getPropertyModel()->className(), ['id_object' => 'id']);
    }
}