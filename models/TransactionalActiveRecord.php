<?php
namespace core\models;

use Yii;
use core\models\ActiveRecord;
use yii\base\Exception;

/**
 * AR в котором сохранение и удаление выполняется в транзакции
 * 
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class TransactionalActiveRecord extends ActiveRecord 
{
    /**
     *  save base model with all properties
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        return self::getDb()->transaction(function($db) use($runValidation, $attributeNames){
            $this->beforeSaveModel();
            if (! parent::save($runValidation, $attributeNames)){
                throw new Exception(Yii::t('api','Ошибка сохранения, основной модели'), 500);
            }
            $this->afterSaveModel();

            return true;
        });
    }
    
    /**
     * actions before model save
     */
    public function beforeSaveModel()
    {
        
    }
    
    /**
     * actions after model save
     */
    public function afterSaveModel()
    {
        
    }

    /**
     * delete model
     * 
     * @return boolean
     */
    public function delete()
    {
        return self::getDb()->transaction(function($db) {
            $this->beforeDeleteModel();
            if (! parent::delete()){
                throw new Exception(Yii::t('api','Ошибка удаления, основной модели'), 500);
            }  
            $this->afterDeleteProps();

            return true;
        });
    }
    
    
    /**
     * actions after model delete
     */
    public function afterDeleteModel()
    {
        
    }
    
    /**
     * actions before model delete
     */
    public function beforeDeleteModel()
    {
        
    }
}