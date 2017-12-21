<?php
namespace core\analytics;

use core\analytics\BaseQuery;
use MongoDB\BSON\UTCDateTime;

/**
 * Base class for long term and non dynamic analytics collect
 * 
 * @property AR $targetModelClass            - target model class
 * @property AR $targetReplicationModelClass - target replication model class
 * @property boolean $by_current_date        - поиск мое\дели для редактирования с учетом даты или нет
 * @property date $reportDate                - дата которая будет выставлена как дата сбора информации
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class Query extends BaseQuery
{
    public $targetModelClass;
    public $targetReplicationModelClass;
    public $by_current_date = true;
    public $reportDate;
    
    /**
     * выполняет запрос и обработку данных
     * 
     * @param array $inputData
     * @return type
     */
    public static function query($dateFrom = null, $dateTo = null, &$inputData = null)
    {
        $query = new static($dateFrom, $dateTo);
        $targetModelClass = $query->targetModelClass;
        return $targetModelClass::getDb()->transaction(function($db) use ($query, $inputData){
            $query->execute($inputData);

            return true;
        });
    }
    
    /**
     * @see \console\modules\analytics\collectors\base\BaseQuery
     */
    public function processData(&$data, &$inputData = null)
    {
        $model = $this->getTargetModelForUpdate($data);
        if ($model){
            $isUpdate = true;
        }
        $model = $this->saveData($data, $model);
        if ($isUpdate){
            echo $this->targetModelClass." UPDATING row with id = {$model->id} FOR {$model->date} ... ".PHP_EOL;
        }else{
            $date = $model->date ? $model->date : $this->dateTo;
            echo $this->targetModelClass." CREATING row with id = {$model->id} FOR {$date} ... ".PHP_EOL;
        }
    }
    
    /**
     * Получить модель для редактирования
     */
    public function getTargetModelForUpdate($data)
    {
        $replicationModelClass = $this->targetReplicationModelClass;
        $params = $this->getReplicationModelSearchParams($data);
        // Потому что отчеты обновляем только по дням, если дата отличается то это новый отчет
        //TODO ТОлько для монги если будем добавлять еще надо будет вынести в класс наследник
        if ($this->by_current_date){
            $this->getByDateParams($params);
        }
        $replicationModel = $replicationModelClass::find()
                                ->where($params)
                                ->asArray()
                                ->one();
        if (! $replicationModel){

            return null;
        }
        $modelClass = $this->targetModelClass;

        return $this->getTargetModelById($replicationModel['id']);
    }
    
    /**
     * Получить основную модель по id
     * @param type $id
     * @return type
     */
    public function getTargetModelById($id, $by_current_date = false)
    {
        $params = [
            'id'=>(int)$id
        ];
        $modelClass = $this->targetModelClass;
        
        return $modelClass::find()
                ->where($params)
                ->one();
    }
    
    /**
     * Сохраняем или редактируем данные
     */
    public function saveData(&$data, $model = null)
    {
        $model = $model ? $model : new $this->targetModelClass();
        //Считываем публичные свойства модели, т.к. согласно архитектуре работаем только с ними 
        $props = get_object_vars($model);
        foreach ($props as $name=>$value) {
            // Если в $data нет свойства то пропускаем
            if (! isset($data[$name])){
                continue;
            }
            $model->{$name} = $data[$name];
        }
        // если указана явно дата сбора инфы то устанавливаем в модель
        if ($this->dateTo){
            $model->date = $this->dateTo;
        }
        if (! $model->save()){
            print_r($model->getErrors());
            //throw new Exception(Yii::t('api','Не удалось сохранить модель.'), 500);
        }
        
        return $model;
    }
    
    /**
     * возвращщает массив для поиска по реплике
     * типы данных должны быть явно приведены
     */
    abstract function getReplicationModelSearchParams($data);
    
    /**
     * Получить параметры для запроса по дате для разных бд
     */
    abstract function getByDateParams(&$params);
}
