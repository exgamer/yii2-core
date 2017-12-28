<?php
namespace core\models;

use core\models\ActiveRecord;

/**
 * Класс реализует дерево объектов
 * если основная таблица называется abc
 * иерархическая структура в виде дерева будет храниться
 * в тиблице abc_tree
 * 
 * Closure Table pattern
 * 
 * @author Kamaelkz
 */
abstract class ClosureTable extends ActiveRecord
{
    //public $parent_id;

    const POSTFIX = '_tree';
      
    protected $_tableName;
    
    protected $_treeTableName;

    public function init()
    {
        parent::init();
        $this->_tableName = static::tableName();
        $this->setTreeTableName();
    }
    
    /**
     * Устанавливает название таблицы дерева
     */
    public function setTreeTableName()
    {
        $this->_treeTableName =  $this->_tableName . self::POSTFIX;
    }
    
    /**
     * После сохранения еденицы строит иерархическое дерево
     * 
     * @param boolean $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes) 
    {
        parent::afterSave($insert, $changedAttributes);
        $this->unbind();
        $this->bind();
    }

        /**
     * Получение потомков объекта
     * 
     * @return array
     */
    public function getChild()
    {
        $sql = 
        "
            SELECT * FROM {$this->_tableName} o
            JOIN {$this->_treeTableName} ot ON (o.id = ot.id_child)
            WHERE ot.id_parent = :ID
        ";

        $command = self::getDb()->createCommand($sql);
        $command->bindValue(':ID', $this->id);
        $result = $command->queryAll();
        
        return $result;
    }
    
    /**
     * Получение предков объекта
     * 
     * @return array
     */
    public function getParent()
    {
        $sql = 
        "
            SELECT * FROM {$this->_tableName} o
            JOIN {$this->_treeTableName} ot ON (o.id = ot.id_parent)
            WHERE ot.id_child = :ID
            ORDER BY ot.level DESC
        ";
               
        $command = self::getDb()->createCommand($sql);
        $command->bindValue(':ID', $this->id);
        $result = $command->queryAll();
        
        return $result;
    }
    
    /**
     * Вставка узла
     */
    protected function bind()
    {
        $sql  = "
                INSERT INTO {$this->_treeTableName} (id_parent,id_child,level,is_root,path)
                SELECT  at.id_parent,
                        :ID::int,
                        (CASE WHEN at.level <= 0 THEN 0 else at.level END) + 1,
                        :IS_ROOT::int,
                        CASE WHEN at.path IS NULL THEN  at.path ELSE (path || '.' || :NAME) END
                FROM {$this->_treeTableName} at WHERE at.id_child=:ID_PARENT
                union all 
                select :ID::int,:ID::int,0,:IS_ROOT::int,:NAME
                ON CONFLICT(id_parent, id_child, level) 
                DO UPDATE 
                SET path =  excluded.path,
                            is_root = excluded.is_root
        ";
        self::getDb()->createCommand(
                $sql,
                [
                        ':ID'=>$this->id,
                        ':ID_PARENT'=>$this->{PARENT_ID_FIELD},
                        ':IS_ROOT'=>($this->{PARENT_ID_FIELD} && $this->{PARENT_ID_FIELD} > 0) ? 0 :1,
                        ':NAME'=> property_exists($this, 'name') ? $this->name : null,
                ]
        )
        ->execute();
    }
    
    /**
     * Удаление узла
     * 
     * @return boolean
     */
    protected function unbind()
    {
        $sql = "
           DELETE FROM {$this->_treeTableName}
           WHERE id_child = :ID
        ";

        $command = self::getDb()->createCommand($sql);
        $command->bindValue(':ID' ,$this->id);
        $command->execute();
    }
    
    /**
     * Передвижение узла по дереву
     * 
     * @param integer $target_id
     */
    protected function move($target_id)
    {
        $sql = "
           DELETE a FROM {$this->_treeTableName} a
           JOIN {$this->_treeTableName} d ON a.id_child = d.id_child
           LEFT JOIN {$this->_treeTableName} x ON x.id_parent = d.id_parent AND x.id_child = a.id_parent
           WHERE d.id_parent = :ID and x.id_parent IS NULL
        ";
           
        $command = self::getDb()->createCommand($sql);
        $command->bindValue(':ID' ,$this->id);
        $command->execute();

        $sql = "
            INSERT INTO {$this->_treeTableName} (id_parent,id_child , level)
            SELECT a.id_parent, b.id_child , a.level + b.level + 1
            FROM {$this->_treeTableName} a
            JOIN {$this->_treeTableName} b
            WHERE b.id_parent = :ID
            AND a.id_child = :IDTARGER
        ";

        $command = self::getDb()->createCommand($sql);
        $command->bindValue(':ID' ,$this->id);
        $command->bindValue(':IDTARGER' ,$target_id);
        $command->execute();
       
        static::updateAll([PARENT_ID_FIELD => $target_id] , ['id' => $this->id]);

        return true;
    }
}