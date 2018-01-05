<?php
namespace core\traits;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Трейт для работы обработки экспандов
 * Добавляет к основному запросу жадную загрузку релейшинов по вызываемым экспандам
 * Работает по общему принципу
 *                $query->with(["children" => function($q){
 *                    $q->with(["child" => function ($q) {
 *                          $q->notDeleted();
 *                          $q->active();
 *                    }]);
 *                    $q->notDeleted();
 *                    $q->active();
 *                }]);
 * Если в запрос замыкания необходимо добавить дополнительные условия
 * необходимо прописывать подключение релейшенов явно
 *                if ($this->isExpandAttribute("children")) {
 *                      $query->with(["children" => function($q){
 *                          $q->andWhere("0 = 1")
 *                          $q->notDeleted();
 *                          $q->active();
 *                      }]);
 *                }
 * 
 * Если прописан isExpandAttribute то автопдключение не происходит
 * @author Kamaelkz
 */
trait ExpandTrait
{  
    /**
     * Мапа подключенных релейшинов
     * для фильтрации подключения дубликатов
     * 
     * @var array 
     */
    private $_includeMap = [];
    
    /**
     * Массив параметров переданных в параметры запроса expand
     * @var array 
     */
    private $_expandParams = [];
    
    /**
     * Массив дерева экспандов
     * 
     * @var array 
     */
    private $_expandTree = [];

    /**
     * Проверяет пришедшие в запросе экспанды и делает жадную загрузку
     * данных по мере необходимости
     * 
     * @param ActiveQuery $query
     * @param BaseSearch $search
     */
    public function checkExpand(&$query, $search = null)
    {
        $this->setExpandParams();
        $this->setExpandTree();
        $this->setExpandWith($query, $this->_expandTree, $this);
    }
    
    /**
     * Проверяет передан ли атрибут в параметр expand
     * 
     * @param string $attr
     * @return boolean
     */
    public function isExpandAttribute($attr)
    {
        $this->setExpandParams();
        foreach ($this->_expandParams as $expand) {
            $parts = explode(".", $expand);
            foreach ($parts as $index => $v) {
                if(in_array($attr, $parts)){
                    $this->pushMap($attr, $index);

                    return true;
                }
            }
        }
        
        return false;
    }

    private function setExpandWith($query, $branch, $model, $i = 0)
    {    
        if(! is_array($branch)){
            return;
        }
        foreach ($branch as $key => $value){
            if($this->inMap($key, $i)){
                continue;
            }
            $this->pushMap($key, $i);
            $relationModel = $this->checkRelation($key, $model);
            if(! $relationModel){
                $relationModel = $model;
                continue;
            }
            $query->with([$key => function($q) use($value, $i, $relationModel){
                $i ++;
                $q->notDeleted();
                $q->active();
                $this->setExpandWith($q, $value, $relationModel, $i);
            }]);
        }
    }
    
    /**
     * Формирование дерева экспандов
     */
    private function setExpandTree()
    {
        if(! $this->_expandParams){
            return;
        }
        if($this->_expandTree){
            return;
        }
        foreach ($this->_expandParams as $param) {
            $cursor = &$this->_expandTree;
            $parts = explode(".", $param);
            foreach ($parts as $part) {
                $cursor = &$cursor[$part];
            }
        } 
    }
    
    /**
     * Возвращает объект модели релейшина b и валидирует ее по ряду признаков
     * подключать жандную загрузку
     * 
     * @param ActiveRecord $object
     * @param string $expand
     * @param ActiveRecord $object
     * @return ActiveRecord
     */
    private function checkRelation($expand, ActiveRecord $model)
    {
        $method = "get{$expand}";
        #существование метода
        if(! method_exists($model, $method)){
            return null;
        }
        $extraFields = $model->extraFields();
        #проверка на нахождение в массиве extraFields модели
        if(! in_array($expand, $extraFields)){
            return null;
        }
        $result = $model->{$method}();
        #экземпляр ActiveQuery
        if(! $result instanceof ActiveQuery){
            return null;
        }
        
        return new $result->modelClass();
    }

    /**
     * Устанавливает массив элементов из параметра expand запроса
     */
    private function setExpandParams()
    {
        if($this->_expandParams){
            return null;
        }
        $this->_expandParams =  explode(",", Yii::$app->request->get("expand"));
    }
    
    /**
     * Формирует ключ мапы
     * 
     * @param sting $v
     * @param integer $index
     * @return string
     */
    private function getMapKey($v, $index)
    {
        if($index === 0){
            return $v;
        }
        $r = [];
        for($i = 0;$i < $index; $i++){
            $r[] = '*.';
        }
        $r[] = $v;

        return implode('', $r);
    }
    
    /**
     * Заносит экспанд в мапу
     * 
     * @param string $v
     */
    private function pushMap($v, $index)
    {
        if(in_array($this->getMapKey($v, $index), $this->_includeMap)){
            return;
        }
        $this->_includeMap[$this->getMapKey($v, $index)] = $v;
    }
    
    /**
     * Проверяет находится ли элемент экспанда в мапе
     * 
     * @param string $v
     * @return boolean
     */
    private function inMap($v, $index)
    {
        if(! in_array($this->getMapKey($v, $index), $this->_includeMap)){
            return false;
        }
        
        return false;
    }
}
