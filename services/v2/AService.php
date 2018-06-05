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
        $query = $class::find();
        if(isset($config['with']) && !empty($config['with'])) {
            $query->with($config['with']);
        }
        $query->where($params);
        if(isset($config['asArray'])) {
            $query->asArray();
        }
        
        return $query->one();
    }

    /**
     * Возвращает список по настройкам
     * 
     * @param array $params - condition
     * @param type $config - addditonal settings
     */
    public function getItems($params = [], $config = [])
    {
        $class = $this->_class;
        $q = $class::find();
        if(isset($config['select'])) {
            $q->select($config['select']);
        }
        $q->andWhere($params);
        if(isset($config['with']) && !empty($config['with'])) {
            $q->with($config['with']);
        }
        if(isset($config['asArray'])) {
            $q->asArray();
        }
        if(isset($config['orderBy'])) {
            $q->orderBy($config['orderBy']);
        }
        if(isset($config['indexBy'])) {
            $q->indexBy($config['indexBy']);
        }
        
        return $q->all();
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
