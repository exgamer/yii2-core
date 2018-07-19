<?php
namespace core\services\v2;

use Yii;
use yii\base\Component;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\helpers\Json;
use core\helpers\v2\DbHelper;

/**
 * Базовый Service для моделей
 * 
 * @property boolean $transactionalModelSave признак показывающий использовать ли транзакцию при созхранении модели
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class AService extends Component
{          
    protected $transactionalModelSave = true;
    public $errors=[];
    
    private $_tableName;
    private $_class;
    
    public function init()
    {
        parent::init();
        list($this->_class, $this->_tableName) = $this->getModelInfo();
    }

    /**
     * @return \yii\db\Connection
     */
    public function getDb()
    {
        $modelClass = $this->getRelatedModelClass();
        
        return $modelClass::getDb();
    }
    
    /**
     * Мультивставка
     * @param array $data
     */
    public function batchData($data)
    {
        DbHelper::batch($this->_class, $data);
    }
    
    /**
     * Получение объекта по идентификатору
     * 
     * @param integer $id
     * @param array $with
     * 
     * @return ActiveRecord
     */
    public function getById($id , $with = [], $config = [])
    {           
        if (! empty($with)){
            $config['with'] = $with;
        }
        
        return $this->getItem(["{$this->_tableName}.id" => $id],  $config);
    }
    
    /**
    * Возвращает модель по условию
    *
    * @param array $condition
    * @param array $with
    * @param array $config
    *
    * @return ActiveRecord
    */
   public function getItem($params = [], $config = [])
   {
       $class = $this->_class;
       $q = $class::find();
       $this->applyQueryCriteria($q, $params, $config);
       
       return $q->one();
   }

    /**
     * Возвращает список по настройкам
     * 
     * @param array $params - condition
     * @param type $config - addditonal settings
     * 
     * @return ActiveRecord
     */
    public function getItems($params = [], $config = [])
    {
        $class = $this->_class;
        $q = $class::find();
        $this->applyQueryCriteria($q, $params, $config);
        
        return $q->all();
    }
    
    /**
     * Кол-во записей
     * 
     * @param array $params - condition
     * @param type $config - addditonal settings
     * 
     * @return ActiveRecord
     */
    public function count($params = [], $config = [])
    {
        $class = $this->_class;
        $q = $class::find();
        $this->applyQueryCriteria($q, $params, $config);
        
        return $q->count();
    }
    
    /**
     * Применение конфига к запросу
     * 
     * @param ActiveQuery $query
     * @param array $params
     * @param array $config
     */
    private function applyQueryCriteria(&$query, $params, $config)
    {
//        if($params) {
//             $query->andWhere($params);
//        }
        /**
         * Для возможности использования не только ключ/значение
         * а еще andWhere('sql' , [params])
         * 
         *  пример
         *   $events = $this->getItemsAsArray([
         *                                           'event_profile_id' => $model->event_profile_id,
         *                                           'weekday' => $model->weekday,
         *                                           'status' => ConstHelper::STATUS_ACTIVE,
         *                                           'is_deleted' => 0,
         *                                           ["string sql condition", [':TIME_START' => $model->time_start]]
         *   ]);
         */
        foreach ($params as $key => $value) {
            if (is_string($key)){
                $modelClass = $query->modelClass;
                // для поиска по jsobB полям
                if (method_exists($modelClass,'properties')){
                    $properties = $modelClass::properties();
                    if (in_array($key, $properties)){
                        $model = new $modelClass();
                        $model->{$key} = $value;
                        if (is_array($value)){
                            $query->setJsonbCondition($model, $key, false, "IN");
                        }else{
                            $query->setJsonbCondition($model, $key);
                        }
        
                    }
                }else{
                    $query->andWhere([$key => $value]);
                }
            }
            if (is_integer($key) && is_array($value)){
                $condition = null;
                if (isset($value[0])){
                    $condition = $value[0];
                }
                $prms = null;
                if (isset($value[1]) && is_array($value[1])){
                    $prms = $value[1];
                }
                if ($condition){
                    $query->andWhere($condition, $prms);
                }
            }
        }
        if(isset($config['select'])) {
            $query->select($config['select']);
        }
        if(isset($config['with']) && !empty($config['with'])) {
            $query->with($config['with']);
        }
        if(isset($config['asArray'])) {
            $query->asArray();
        }
        if(isset($config['orderBy'])) {
            $query->orderBy($config['orderBy']);
        }
        if(isset($config['indexBy'])) {
            $query->indexBy($config['indexBy']);
        }
    }

    /**
     * Сохранение модели
     * 
     * @param ActiveRecord $model
     * @param boolean $validation
     * @throws Exception
     */
    public function saveModel($model, $validation = true)
    {
        if ($this->transactionalModelSave){
            return $this->getDb()->transaction(function($db) use($model, $validation){
                if(! $model->save($validation)){
                        throw new Exception(
                                Yii::t('service','Не удалось сохранить модель - {errors}', [
                                    'errors' => Json::encode($model->getErrors())
                                ])
                        );
                }

                return true;
            });
        }
        
        if(! $model->save($validation)){
                throw new Exception(
                        Yii::t('service','Не удалось сохранить модель - {errors}', [
                            'errors' => Json::encode($model->getErrors())
                        ])
                );
        }

        return true;
    }
    
    /**
     * Получить класс связанной модели
     */
    public abstract function getRelatedModelClass();

    /**
     * Получить ошибки
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Информация о связанной моделе
     * 
     * @return array
     */
    protected function getModelInfo()
    { 
        $class = $this->getRelatedModelClass();
        $tableName = $class::tableName();
        
        return [$class, $tableName];
    }
}

class AServiceException extends Exception 
{
        public function getName()
        {
                return 'ABaseService exception';
        }
}
