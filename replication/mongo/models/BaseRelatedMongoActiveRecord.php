<?php
namespace core\replication\mongo\models;

use yii\mongodb\ActiveRecord;
use yii\base\Exception;

/**
 * replication mongo model related to standard AR
 * 
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract  class BaseRelatedMongoActiveRecord extends ActiveRecord
{   
    protected $attributes_data=[];
    
    /**
     * Получаем правила валидации из связанной модели с учетом первинчых ключей
     * @return array
     * @throws Exception
     */
    public function rules()
    {
            $model = $this->getRelatedModel();
            $schema = $model::getTableSchema();
            $columnsFull=$schema->columns;
            $primaryKeys = $model::primaryKey();
            if (! is_array($primaryKeys) || count($primaryKeys) == 0){
                throw new Exception(Yii::t('api','Для реплицирования модель должна иметь первичный ключ.'), 500);
            }
            $intFields = [];
            foreach ($primaryKeys as $primaryKey) {
                $this->pushIntegerAttribute($intFields, $primaryKey, $columnsFull);
            }
            $relatedRules = $model->rules();
            foreach ($relatedRules as $rule) {
                if (count($rule) > 1 && $rule[1] == 'integer'){
                    if (is_array($rule[0])){
                        $intFields = array_merge($intFields, $rule[0]);
                    }else{
                        $intFields[] = [$rule[0]];
                    }
                }
            }
            $intFields = array_unique($intFields);
            if (count($intFields) == 0 ){
                
                return $model->rules();
            }
            $rules =[ 
                [
                    $intFields,
                    'filter',
                    'filter'=>function($value){
                        return (int) $value;
                    }
                ]
            ];
                
            return array_merge($model->rules(), $rules);
    }
    
    public function pushIntegerAttribute(&$array, $attributeName, $columnsFull)
    {
        $attr = $columnsFull[$attributeName];
        if (isset($attr->phpType) && $attr->phpType == 'integer'){
            $array[] = $attributeName;
        } 
    }
    
    /**
     * Динамически собираем аттрибуты согласно связанной модели
     * @return array
     * @throws Exception
     */
    public function attributes()
    {
        if(! empty($attributes_data)){
            return $this->attributes_data;
        }
        $model =$this->getRelatedModel();
        $primaryKeys = $model::primaryKey();
        if (! is_array($primaryKeys) || count($primaryKeys) == 0){
            throw new Exception(Yii::t('api','Для реплицирования модель должна иметь первичный ключ.'), 500);
        }
        $attributes_data[] = '_id';
        foreach ($primaryKeys as $primaryKey) {
            $attributes_data[] = $primaryKey;
        }
        foreach ($model->attributes as $name => $value) {
            $attributes_data[] = $name;
        }
        $props = get_object_vars($model);
        foreach ($props as $name=>$value) {
            $attributes_data[] = $name;
        }
        $this->attributes_data = array_unique($attributes_data);

        return $this->attributes_data;
    }
    
    public function getRelatedModel()
    {
        $modelClass = $this->getRelatedModelClass();
        if (! $modelClass){
            throw new Exception(Yii::t('api','Не указан класс связанной модели.'), 500);
        }
        return new $modelClass();
    }
    
    /**
     * returns origin related models class
     * 
     * returns string
     */
    public static function getRelatedModelClass(){}
    
}