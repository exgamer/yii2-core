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
            $localData = empty($remoteData)?[]:$this->getLocalData();
        }else{
            $localData = $this->getLocalData();
            $remoteData = $this->getData();
        }

        return $this->mergeData($localData, $remoteData);
    }
}

