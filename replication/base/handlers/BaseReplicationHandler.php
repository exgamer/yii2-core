<?php
namespace core\replication\base\handlers;

use yii\db\ActiveRecord;

/**
 * Base replication handler 
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class BaseReplicationHandler
{
    /**
     * save replication data
     */
    public function save(ActiveRecord $model)
    {
        $this->beforeSave($model);
        $this->saveActions($model);
        $this->afterSave($model);
        
        return true;
    }
    
    /**
     * actions before save
     */
    public function beforeSave(ActiveRecord $model)
    {
        
    }
    
    /**
     * actions after save
     */
    public function afterSave(ActiveRecord $model)
    {
        
    }
    
    /**
     * delete replication data
     */
    public function delete(ActiveRecord $model)
    {
        $this->beforeDelete($model);
        $this->deleteActions($model);
        $this->afterDelete($model);
        
        return true;
    }
    
    /**
     * actions before delete
     */
    public function beforeDelete(ActiveRecord $model)
    {
        
    }
    
    /**
     * actions after delete
     */
    public function afterDelete(ActiveRecord $model)
    {
        
    }
    
    /**
     * save base actions
     */
    abstract function saveActions(ActiveRecord $model);
    
    /**
     * delete base actions
     */
    abstract function deleteActions(ActiveRecord $model);
}