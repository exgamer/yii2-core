<?php
namespace core\data;

use Yii;
use yii\db\QueryInterface;
use yii\base\InvalidConfigException;

/**
 * 
 * @property boolean $asArray параметр указывает вернуть ли данные в виде массива или AR
 */
class CacheDataProvider extends \yii\data\ActiveDataProvider
{
    public $asArray = true;
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }
    /**
     * @inheritdoc
     */
    protected function prepareModels()
    {
        if (!$this->query instanceof QueryInterface) {
            throw new InvalidConfigException('The "query" property must be an instance of a class that implements the QueryInterface e.g. yii\db\Query or its subclasses.');
        }
        $query = clone $this->query;
        if (($pagination = $this->getPagination()) !== false) {
            $pagination->totalCount = $this->getTotalCount();
            $query->limit($pagination->getLimit())->offset($pagination->getOffset());
        }
        if (($sort = $this->getSort()) !== false) {
            $query->addOrderBy($sort->getOrders());
        }
        
        return \Yii::$app->db->cache(function ($db) use ($query) {
            $this->resolveRelations($query);

            return $query->all($db);
        },3600,$this->getDependency());
    }
    
    /**
     * Выставление релейшенов если надо вернуть данные в виде массива
     * @param type $query
     */
    public function resolveRelations(&$query)
    {
        if (is_bool($this->asArray) && $this->asArray){
            $modelClass = $this->query->modelClass;
            $model = new $modelClass();
            $relations = $model->extraFields();
            $with = null;
            $exp = Yii::$app->request->get('expand');
            if($exp != null){
                $temp = [];
                $exp = explode(',',$exp);
                foreach ($exp as  $value) {
                    $temp[]=trim($value);
                }
                $exp = $temp;
            }
            foreach ($relations as $relation) {
                $methodName = ucfirst(strtolower($relation));
                if (method_exists($modelClass, 'get'.$methodName)){
                    if ($exp && is_array($exp) && in_array($relation, $exp)){
                        $with[] = $relation;
                    }
                }
            }
            if ($with && is_array($with)){
                $query->with($with);
            }
            $query->asArray();
        }
    }
    
    /**
     * @inheritdoc
     */
    protected function prepareTotalCount()
    {
        if (!$this->query instanceof QueryInterface) {
            throw new InvalidConfigException('The "query" property must be an instance of a class that implements the QueryInterface e.g. yii\db\Query or its subclasses.');
        }
        $query = clone $this->query;
        $db = $this->db;
        if($db === null){
            $modelClass = $this->query->modelClass;
            $db = $modelClass::getDb();
        }
        return $db->cache(function($db) use($query){
            return (int) $query->limit(-1)->offset(-1)->orderBy([])->count('*', $db);
        },3600,$this->getDependency());
    }
    
    public function getDependency()
    {
        return new \yii\caching\TagDependency(['tags' => $this->query->modelClass]);
    }
}
?>