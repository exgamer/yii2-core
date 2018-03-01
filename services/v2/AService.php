<?php
namespace core\services\v2;

use Yii;
use yii\base\Component;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\helpers\Json;

/**
 * Базовый Service для моделей
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class AService extends Component
{          
    public $errors=[];
    
    /**
     * @return \yii\db\Connection
     */
    public function getDb()
    {
        $modelClass = $this->getRelatedModelClass();
        
        return $modelClass::getDb();
    }
    
    /**
     * Получение объекта по идентификатору
     * 
     * @param string $modelClass
     * @param integer $id
     * @param array $with
     * 
     * @return ActiveRecord
     */
    public  function getById($modelClass, $id , $with = [])
    {       
        $tableName = $modelClass::tableName();
        $query = $modelClass::find();
        if(! empty($with)){
            $query->with($with);
        }
        $query->where(["{$tableName}.id" => $id]);

        return $query->one();
    }
    
    /**
     * Сохранение модели
     * 
     * @param ActiveRecord $model
     * @param boolean $validation
     * @throws Exception
     */
    public function saveModel(ActiveRecord $model, $validation = true)
    {
        return $this->getDb()->transaction(function($db) use($model, $validation){
            if(! $model->save($validation)){
                    throw new Exception(
                            Yii::t('service','Не удалось сохранить модель - {errors}', [
                                'errors' => Json::encode($model->getErrors())
                            ])
                    );
            }
        });
    }
    
    /**
     * Возвращает список по настройкам
     * 
     * @param string $modelClass - класс с namespace
     * @param array $params - condition
     * @param type $config - addditonal settings
     */
    public function getItems($modelClass, $params = [], $config = [])
    {
        $table = $modelClass::tableName();
        $q = $modelClass::find();
        if(isset($config['select'])) {
            $q->select($config['select']);
        }
        $q->andWhere($params);
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
}

class AServiceException extends Exception 
{
        public function getName()
        {
                return 'ABaseService exception';
        }
}