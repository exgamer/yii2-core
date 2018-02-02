<?php
namespace core\data;

use yii\data\ActiveDataProvider;
use yii\db\QueryInterface;
use yii\base\InvalidConfigException;
use common\components\queries\RemoteBaseActiveQuery;
use common\components\queries\MixedRemoteBaseActiveQuery;
use core\queries\ActiveQuery;
/**
 * ActiveDataProvider for RemoteActiveRecords
 * 
 * @author CitizenZet <exgamer@live.ru>
 * @since 2.0
 */
class RemoteActiveDataProvider extends ActiveDataProvider
{
    protected function prepareModels()
    {
        if (!$this->query instanceof QueryInterface) {
            throw new InvalidConfigException('The "query" property must be an instance of a class that implements the QueryInterface e.g. yii\db\Query or its subclasses.');
        }
        $query = clone $this->query;
        if (($pagination = $this->getPagination()) !== false) {
            $pagination->totalCount = $this->getTotalCount();
            if ($pagination->totalCount === 0) {
                return [];
            }
            $query = clone $this->query;
            $query->limit($pagination->getLimit())->offset($pagination->getOffset());
        }
        if (($sort = $this->getSort()) !== false) {
            $query->addOrderBy($sort->getOrders());
        }
        return $query->all($this->db);
    }
    /**
     * Переопределяем метод для того чтобы если не находятся записи тут, находить их на удаленном
     * @see yii\data\ActiveDataProvider
     */
    protected function prepareTotalCount()
    {
        $modelClass = $this->query->modelClass;
        if ((property_exists($modelClass, 'findLocally') && $modelClass::$findLocally) || $this->query instanceof RemoteBaseActiveQuery || !$this->query instanceof MixedRemoteBaseActiveQuery){
            return parent::prepareTotalCount();
        }
        if (!$this->query instanceof QueryInterface) {
            throw new InvalidConfigException('The "query" property must be an instance of a class that implements the QueryInterface e.g. yii\db\Query or its subclasses.');
        }
        
        $query = clone $this->query;
        /**
         * Вырубаем из запроса remote fields
         */
        $query->splitWhere();
        $query->buildSearchParams();
        /**
         * Если есть remote поля выставляем метку для основного запроса
         */
        $this->query->setSearchByRemoteFields($query->isSearchByRemoteFields());
        if (! $this->query->isSearchByRemoteFields()){
            return (int)$this->getLocalDataCount($query);
        }
        $query->setExpand();
        $this->query->remoteData = $query->getData();
        $localCount = $this->getLocalDataCount($query);
        if (($pagination = $this->getPagination()) !== false) {
            $pagination->totalCount = $localCount;
            if ($pagination->totalCount === 0) {
                return [];
            }
            $query->limit($pagination->getLimit())->offset($pagination->getOffset());
        }
        if (($sort = $this->getSort()) !== false) {
            $query->addOrderBy($sort->getOrders());
        }
        $this->query->localData = $query->getLocalData();
        return (int) $localCount;
    }
    
    public function getLocalDataCount($query)
    {
        $q = new ActiveQuery($this->query->modelClass);
        $q->where = $query->where;
        $q->joinWith = $query->joinWith;
        $q->on = $query->on;
        $q->alias = $query->alias;
        return $q->limit(-1)->offset(-1)->orderBy([])->count('*', $this->db);
    }
}
