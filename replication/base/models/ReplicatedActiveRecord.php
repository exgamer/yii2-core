<?php
namespace core\replication\base\models;

use Yii;
use core\models\TransactionalActiveRecord;
use yii\base\Exception;

/**
 * Класс предназначен для репликации данных в другое место
 * 
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class ReplicatedActiveRecord extends TransactionalActiveRecord 
{
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
     * get replication handler
     */
    abstract function getReplicationHandler();
}