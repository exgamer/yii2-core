<?php
namespace core\queries;

use core\queries\ActiveQuery;
use core\helpers\ConstHelper;

/**
 * Базовый ActiveQuery
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
class BaseActiveQuery extends ActiveQuery
{
    use \core\traits\QuerySearchSetTrait;
    use \core\models\v2\properties\ActiveQueryPropsTrait;

    /**
     * @see \core\models\v2\properties\ActiveQueryPropsTrait
     */
    public function one($db = null)
    {
        $this->setModelProperties();

        return parent::one($db);
    }
    
    /**
     * @see \core\models\v2\properties\ActiveQueryPropsTrait
     */
    public function all($db = null)
    {
        $this->setModelProperties();
        
        return parent::all($db);
    }

    /**
     * Масссив для расширения полей возврата для модели
     * передается в модель
     * 
     * @var array
     */
    protected $extendModelFieldsMap = [];
    
    /**
     * Возращает альяс таблицы
     * 
     * @param string $alias
     * @return string
     */
    public function getAlias($alias=null)
    {
        if ($alias){
            return $alias;
        }
        
        return $this->alias;
    }
    
    /**
     * Добавления условия при котором запрос вернет пустые данные
     */
    public function emptyData()
    {
        $this->andWhere("1 = 0");
    }

        /**
     * only active
     * @return ActiveQuery
     */
    public function active($alias=null)
    {
        if (!$this->checkAttribute('status')){
            return $this;
        }

        return $this->andOnCondition([$this->getAlias($alias).".status"=>ConstHelper::STATUS_ACTIVE]);
    }
    
    /**
     * only deleted
     * @return ActiveQuery
     */
    public function removed($alias=null)
    {
        if (!$this->checkAttribute('status')){
            return $this;
        }
        
        //return $this->andWhere([$this->alias.".".STATUS_FIELD=>ConstHelper::STATUS_REMOVED]);
        return $this->andOnCondition([$this->getAlias($alias).".status"=>ConstHelper::STATUS_REMOVED]);
    }
    
    /**
     * Не удаленная запись
     * @return static
     */
    public function notDeleted($alias=null)
    {
        if (!$this->checkAttribute('is_deleted')){
            return $this;
        }
        
        return $this->andOnCondition([$this->getAlias($alias).".is_deleted"=>false]);
    }
    
    /**
     * check if attribute exists
     * @param type $attr_name
     * @return boolean
     */
    public function checkAttribute($attr_name)
    {
        $instance = $this->getModelInstance();
        if (in_array($attr_name, ($instance->attributes()))){
           return true;
        }
        
        return false;
    }
    
    /**
     * Добвление раширяющего поля в массив $extendModelFieldsMap
     * 
     * @param string $name
     */
    public function pushExtendField($name)
    {
        $this->extendModelFieldsMap[$name] = $name;
    }
    
    /**
     * Получение массива расширяющих полей
     * 
     * @return array
     */
    public function getExtendModelFieldsMap()
    {
        return $this->extendModelFieldsMap;
    }
    
    /**
     * Переопределенный метод создания массива объектов модели
     * из массива запроса
     * Переопределил для передачи в модель значения $extendModelFieldsMap
     * 
     * @override
     * @see yii\db\ActiveQuery
     * @param array $rows
     * @return array
     */
    protected function createModels($rows)
    {
        $models = [];
        if ($this->asArray) {
            if ($this->indexBy === null) {
                return $rows;
            }
            foreach ($rows as $row) {
                $key = $this->getIndexKey($row);
                $models[$key] = $row;
            }
        } else {
            /* @var $class ActiveRecord */
            $class = $this->modelClass;
            /* @var $class ActiveRecord */
            if ($this->indexBy === null) {
                foreach ($rows as $row) {
                    $model = $class::instantiate($row);
                    $this->checkModelFieldsExtend($model);
                    $modelClass = get_class($model);
                    $modelClass::populateRecord($model, $row);
                    $models[] = $model;
                }
            } else {
                foreach ($rows as $row) {
                    $model = $class::instantiate($row);
                    $this->checkModelFieldsExtend($model);
                    $modelClass = get_class($model);
                    $modelClass::populateRecord($model, $row);
                    $key = $this->getIndexKey($row);
                    $models[$key] = $model;
                }
            }
        }

        return $models;
    }
    
    /**
     * получить ключ для массива
     * @param type $row
     * @return type
     */
    protected function getIndexKey($row)
    {
        if (is_string($this->indexBy)) {
            return $row[$this->indexBy];
        } else {
            return call_user_func($this->indexBy, $row);
        }
    }
    
    /**
     * Проверка на существования у модели метода
     * setExtendModelFieldsMap если есть передает свойству 
     * extendModelFieldsMap модели свойство extendModelFieldsMap query
     * @see common\models\base\BaseActiveRecord
     * 
     * @param BaseActiveRecord $model
     */
    protected function checkModelFieldsExtend($model)
    {
        if(! method_exists($model, 'setExtendModelFieldsMap')){
            return null;
        }
        if(count($this->extendModelFieldsMap) > 0){
            $model->setExtendModelFieldsMap($this->extendModelFieldsMap);
        }
    }
    
    /**
     * Возвращает экземпляр модели $this->modelClass
     * 
     * @return ActiveRecord
     */
    protected function getModelInstance()
    {
        static $modelClass;
        static $instance;
        
        if(! $instance || $this->modelClass != $modelClass) {
            $instance = new $this->modelClass();
        } 
        
        return $instance;
    }
}