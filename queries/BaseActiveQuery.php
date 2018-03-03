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
        static $modelClass;
        static $instance;
        
        if(! $instance || $this->modelClass != $modelClass) {
            $instance = new $this->modelClass();
        } 
        if (in_array($attr_name, ($instance->attributes()))){
           return true;
        }
        
        return false;
    }
    
    /**
    * Переопределeно потому что при создании в оригинале не self, а ActiveQuery
    * @see \yii\db\ActiveRecord::viaTable()
     */
    public function viaTable($tableName, $link, callable $callable = null)
    {
        $relation = new self(get_class($this->primaryModel), [
            'from' => [$tableName],
            'link' => $link,
            'multiple' => true,
            'asArray' => true,
        ]);
        $this->via = $relation;
        if ($callable !== null) {
            call_user_func($callable, $relation);
        }

        return $this;
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
        $class = $this->modelClass;
        if ($this->asArray) {
            foreach ($rows as $row) {
                #core\models\v2\properties\column\IARWithColumnProps
                $this->columnProperiesARAsArray($class, $row);
                if ($this->indexBy) {
                    $key = $this->getIndexKey($row);
                    $models[$key] = $row;
                } else {
                    $models[] = $row;
                }
            }
            return $models;
        } else {
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
     * Заполнение дополнительных свойств массива 
     * core\models\v2\properties\column\IARWithColumnProps
     * 
     * @param string $modelClass
     * @param array $row
     */
    protected function columnProperiesARAsArray($modelClass, &$row)
    {
        if(method_exists($modelClass, 'properties') && method_exists($modelClass, 'propertiesColumn')) {
            $properties = $modelClass::properties();
            $propertiesColumn = $modelClass::propertiesColumn();
            if($properties && is_array($properties) && ! empty($row[$propertiesColumn])) {
                $props = \yii\helpers\Json::decode($row[$propertiesColumn]);
                if($props) {
                    foreach ($props as $key => $value) {
                        $row[$key] = $value;
                    } 
                }
                unset($row[$propertiesColumn]);
            }
        }
    }
}
