<?php
namespace core\data;

use yii\data\ActiveDataProvider;
use yii\db\QueryInterface;
use yii\base\InvalidConfigException;
use common\components\queries\RemoteBaseActiveQuery;
use common\components\queries\MixedRemoteBaseActiveQuery;

/**
 * ActiveDataProvider for RemoteActiveRecords
 * 
 * @author CitizenZet <exgamer@live.ru>
 * @since 2.0
 */
class RemoteActiveDataProvider extends ActiveDataProvider
{
    /**
     * Переопределяем метод для того чтобы если не находятся записи тут, находить их на удаленном
     * @see yii\data\ActiveDataProvider
     */
    protected function prepareTotalCount()
    {
        if ( !$this->query instanceof RemoteBaseActiveQuery && !$this->query instanceof MixedRemoteBaseActiveQuery){
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
        /**
         * Если есть remote поля выставляем метку для основного запроса
         */
        $this->query->setSearchByRemoteFields($query->isSearchByRemoteFields());
        $query->beforeQuery();
        $this->query->remoteData = $query->getData();
        $q = new ActiveQuery($this->query->modelClass);
        $q->where = $query->where;
        $q->joinWith = $query->joinWith;
        $q->on = $query->on;
        $q->alias = $query->alias;
        $localCount = $q->limit(-1)->offset(-1)->orderBy([])->count('*', $this->db);
        $this->query->localData = $query->getLocalData();
        
        return (int) $localCount;
    }
}
