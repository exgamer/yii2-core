<?php
namespace core\queries;

use core\models\ActiveRecord;
/**
 * Базовый класс запроса. Если хотите добавлять свои SCOPES то создавайте классы в папке \queries
 * Class ActiveQuery
 * @package app\components
 */
class ActiveQuery extends \yii\db\ActiveQuery 
{
    public $alias = null;

    /**
     * Задает алиасы для всех таблиц
     */
    public function init()
    {
        $modelClass = $this->modelClass;
        $tableName = $modelClass::tableName();
        $this->alias = $tableName;
        parent::init();
    }

    /**
     * Не удаленная запись
     * MyAr::find()->notDeleted()-> ...
     * @return static
     */
    public function notDeleted()
    {

        $modelClass = $this->modelClass;

        if (in_array("is_deleted", ((new $modelClass)->attributes()))){
            $this->andWhere($this->alias . ".is_deleted = :d OR ".$this->alias . ".is_deleted IS NULL", [
                ":d" => 0
            ]);
        }

        if (in_array("state", ((new $modelClass)->attributes()))){
            $this->andWhere($this->alias . ".state != :d", [
                ":d" => ActiveRecord::STATE_DELETED
            ]);
        }

        return $this;
    }

    /**
     * Выборка по первичному ключу, чтобы после этого можно было еще применять фильтры
     * Стандартный метод MyAr::findOne($id) - неудобно так как сразу вызывает выборку
     * С помощью byPk - Myar::find()->byPk($id)->...->...->one()
     * - позволяет вызывать дальше фильтры и тд и потом выборку сделать методом one()
     * @param $value
     * @return static
     */
    public function byPk($value)
    {
        $model = $this->modelClass;
        $pks = $model::primaryKey();
        $condition = [];
        
        if (!is_array($value)) {
            $condition[$this->alias.".".$pks[0]] = $value;
        } else {
            foreach ($pks as $key) {
                if (isset($value[$key]) && $value[$key]!=null){
                    $condition[$this->alias.".".$key] = $value[$key];
                }else{
                    throw new \yii\base\Exception("Первичный ключ не полный");
                }
            }
        }

        return $this->andWhere($condition);
    }

}