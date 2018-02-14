<?php
namespace core\traits;


trait RemoteBaseActiveQueryAdditionalTrait
{
 /**
     * @see \yii\db\ActiveQuery
     */
    public function all($db = null)
    {
        $this->beforeQuery();
        return $this->getData();
    }
    
    /**
     * @see \yii\db\ActiveQuery
     */
    public function one($db = null)
    {
        $this->beforeQuery();
        $data = $this->getData();
        if ($data && is_array($data)){
            return array_shift($data);
        }
        
        return null;
    }
    
    /**
     * TODO
     * ПОПРОБОВАТЬ РЕализовать идею с вызовом all в count 
     * т.е. получать данные в count складывать количество лок и ремоут данных
     * и если данные уже получены в getData ничег оне делать
     * 
     * иначе по идее неверно работает пагинация
     * 
     * @param type $q
     * @param type $db
     * @return int
     */
    public function count($q = '*', $db = null)
    {
        return parent::count($q,$db);
    }
    
    //TODO обрубить остальные методы BaseActiveQuery которые мы пока не поддерживаем
    
    public function joinWith($with, $eagerLoading = true, $joinType = 'LEFT JOIN')
    {

    }
    
    public function innerJoinWith($with, $eagerLoading = true)
    {
        
    }
    
    public function onCondition($condition, $params = [])
    {

    }
    
    public function andOnCondition($condition, $params = [])
    {

    }
    
    public function orOnCondition($condition, $params = [])
    {

    }
    
    public function viaTable($tableName, $link, callable $callable = null)
    {

    }
    
    public function alias($alias)
    {

    }
    

    
    public function with()
    {

    }
    
    public function findWith($with, &$models)
    {

    }
}

