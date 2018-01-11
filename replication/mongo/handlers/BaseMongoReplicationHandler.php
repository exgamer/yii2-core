<?php
namespace core\replication\mongo\handlers;

use Yii;
use core\replication\base\handlers\BaseReplicationHandler;
use yii\db\ActiveRecord;
use yii\base\Exception;

/**
 * Base replication handler for mongodb
 * 
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class BaseMongoReplicationHandler extends BaseReplicationHandler
{
    /**
     * @see core\replication\base\handlers\BaseReplicationHandler
     */
    public function saveActions(ActiveRecord $model)
    {
        $replica = $this->getReplicationData($model);
        if (! $replica){
            $replica = $this->getReplicationModel();
        }
        $this->loadReplicationData($model, $replica);
        if (! $replica->save()){
            throw new Exception(Yii::t('api','Не удалось сохранить реплику'), 500);
        }
    }
    
    /**
     * @see core\replication\base\handlers\BaseReplicationHandler
     */
    public function deleteActions(ActiveRecord $model)
    {
        $replica = $this->getReplicationData($model);
        if (! $replica){
            throw new Exception(Yii::t('api','Реплика не найдена.'), 500);
        }
        if (! $replica->delete()){
            throw new Exception(Yii::t('api','Не удалось удалить реплику.'), 500);
        }
    }
    
    /**
     * loads data from model to replica
     * 
     * @param ActiveRecord $model
     * @param \yii\mongodb\ActiveRecord $replica
     */
    public function loadReplicationData(ActiveRecord $model, \yii\mongodb\ActiveRecord &$replica)
    {
        $attrs = $model->attributes;
        $props = get_object_vars($model);
        $data = array_merge($attrs, $props);
        $replica->attributes = $data;
        $primaryKeys = $model::primaryKey();
        if (! is_array($primaryKeys) || count($primaryKeys) == 0){
            throw new Exception(Yii::t('api','Для реплицирования модель должна иметь первичный ключ.'), 500);
        }
        foreach ($primaryKeys as $primaryKey) {
            $replica->{$primaryKey} = $model->{$primaryKey};
        }
    }
    
    /**
     * get replication data by primary key
     * 
     * @param type $model
     * @return AR
     */
    public function getReplicationData(ActiveRecord $model)
    {
        $params = [];
        $class = $this->getReplicationModelClassName();
        $attrs = $model::replicaUniqueAttributes();
        if ($attrs){
            foreach ($attrs as $name => $value) {
                $params[$name] = self::validateMongoValue($name, $model->{$name}, $model);
            }
        }else{
            $primaryKeys = $model::primaryKey();
            if (is_array($primaryKeys)){
                foreach ($primaryKeys as $primaryKey) {
                    $params[$primaryKey] = self::validateMongoValue($primaryKey, $model->{$primaryKey}, $model);
                }
            }
        }
        
        return  $class::find()
                ->where($params)
                ->one();
    }
    
    /**
     * validates attributes value by type for mongo
     * 
     * @param string $attribute
     * @param maed $value
     * @param AR $model
     * @return mixed
     * @throws Exception
     */
    public static function validateMongoValue($attribute, $value, $model)
    {       
        $schema = $model::getTableSchema();
        $columnsFull=$schema->columns;
        if (! isset($columnsFull[$attribute]))
        {
            throw new Exception("Нет такого аттрибута ".$attribute);
        }
        $attr = $columnsFull[$attribute];
        $phpType = 'string';
        if (isset($attr->phpType)){
            $phpType = $attr->phpType;
        }
        switch ($phpType) {
            case 'integer':
                return (int)$value;
                
            default :
                return (string)$value;
        }
    }
    
    /**
     * get replication model object
     * @return \yii\mongodb\ActiveRecord
     * @throws Exception
     */
    public function getReplicationModel()
    {
        $class = $this->getReplicationModelClassName();
        $model =  new $class();
        if (! $model instanceof \yii\mongodb\ActiveRecord){
            throw new Exception(Yii::t('api','Репликационная модель должна быть наследеником \yii\mongodb\ActiveRecord.'), 500);
        }
        
        return $model;
    }
    
    /**
     * check and get replication model classname
     * @return string 
     * @throws Exception
     */
    public function getReplicationModelClassName()
    {
        $class =  $this->getReplicationModelClass();
        if (! $class || $class == ''){
            throw new Exception(Yii::t('api','Не указан класс репликационной модели.'), 500);
        }
        
        return $class;
    }
    
    /**
     * get mongo \yii\mongodb\ActiveRecord classname for replication
     * 
     * returns string 
     */
    abstract function getReplicationModelClass();
}