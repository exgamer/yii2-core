<?php
namespace core\traits;


trait MixedRemoteBaseActiveQueryAdditionalTrait
{
 /**
     * @see \yii\db\ActiveQuery
     */
    public function all($db = null)
    {
        return $this->getQueryData();
    }
    
    /**
     * @see \yii\db\ActiveQuery
     */
    public function one($db = null)
    {
        $result = $this->getQueryData();

        return array_shift($result);
    }
    
    /**
     * Выполняем запросы
     * @return array of AR
     */
    public function getQueryData()
    {
        #подготавливаем почву
        $this->beforeQuery();
        if ($this->isSearchByRemoteFields()){
            $remoteData = $this->getData();
            $localData = $this->getLocalData();
        }else{
            $localData = $this->getLocalData();
            $remoteData = $this->getData();
        }

        return $this->mergeData($localData, $remoteData);
    }
    
    /**
     * Получение локальных данных
     * @return array || array of AR
     */
    public function getLocalData()
    {
        if ($this->localData){
            return $this->localData;
        }
        $model = $this->getModel();
        #индексируем данные по первичному ключу
        $this->indexBy=function($row) use ($model){
            return $this->getIndexKeyByPrimary($model, $row);
        };
        $localData = parent::all();
        if (! $localData){
            return $localData;
        }
        #отрубаем индексирвоание на всякий случай
        $this->indexBy = null;
        #берем ключи от полученных записей
        $ids = array_keys($localData);
        $keyData = $this->getKeyData($ids);
        $this->remoteWhere = array_merge($this->remoteWhere, $keyData);

        return $localData;
    }
}

