<?php
namespace core\replication\base\models;

use Yii;
use core\models\TransactionalActiveRecord;
use yii\base\Exception;

/**
 * Класс предназначен для репликации данных в другое место
 * 
 * @property boolean $onlyReplica - если true то сохраняем только реплику
 * @property boolean $ignoreEmptyValuesOnUpdate - если true то при редактировании пустые атрибуты не будут онбовляться
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class ReplicatedActiveRecord extends TransactionalActiveRecord
{
    public static $onlyReplica = false;
    public static $ignoreEmptyValuesOnUpdate = false;
    
    /**
     *  save base model with all properties
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if(static::$onlyReplica){
            $this->beforeSaveModel();
            $this->afterSaveModel();
            
            return true;
        }
        
        return self::getDb()->transaction(function($db) use($runValidation, $attributeNames){
            $this->beforeSaveModel();
            if (! parent::save($runValidation, $attributeNames)){
                
                return false;
            }
            $this->afterSaveModel();

            return true;
        });
    }
    
    /**
     * @see \core\models\base\TransactionalActiveRecord
     */
    public function afterSaveModel()
    {
        if (! $this->getReplicationHandler()->save($this)){
            throw new Exception(Yii::t('api','Ошибка репликации данных.'), 500);
        }
    }
    
    /**
     * @see \core\models\base\TransactionalActiveRecord
     */
    public function afterDeleteModel()
    {
        if (! $this->getReplicationHandler()->delete($this)){
            throw new Exception(Yii::t('api','Ошибка удаления реплики.'), 500);
        }
    }

    /**
     * Пареметры для поиска реплики
     * 
     *  пример дял монго
     *   return [
     *       'institution_id'=>(int)$this->institution_id,
     *       'date'=>[
     *           '$gte' => new UTCDateTime (( new \DateTime (date('Y-m-d H:i:s' , strtotime($this->date." 00:00:00"))))),
     *           '$lt' => new UTCDateTime (( new \DateTime (date('Y-m-d H:i:s' , strtotime($this->date." 00:00:00" . " +1 days")))))
     *       ]
     *   ];
     * 
     * @return array|null
     */
    public function uniqueSearchParams()
    {
        return null;
    }
    
    /**
     * get replication handler
     */
    abstract function getReplicationHandler();
}