<?php
namespace core\helpers\v2;


/**
 * Вспомогательный класс содержащий полезные функции для работы с базой данных
 * 
 * @author CitizenZet <exgamer@live.ru>
 *
 */
class DbHelper 
{
    /**
     * ==================== BATCH BLOCK=========================================
     */
    
    /**
     * мультивставка
     * 
     * @param string $class
     * @param array $fields
     * @param array $rows
     */
    public static function batch($class, $data)
    {
        $fields = array_keys($data[0]);
        $primaryKeys = $class::primaryKey();
        $insertData = [];
        $updateData = [];
        foreach ($data as $row) {
            if (static::checkRowOnPrimaryKeyExistence($primaryKeys, $row)){
                $updateData[] = array_values($row);
                continue;
            }
            # TODO придумать как убрирать из вставки поля типа id 
            # id на вставку не идет
            unset($row['id']);
            $insertData[] = array_values($row);
        }
        if (!empty($updateData)){
            static::batchUpdate($class, $fields, $updateData);
        }
        if (!empty($insertData)){
            # TODO придумать как убрирать из вставки поля типа id 
            # id на вставку не идет
            if (($key = array_search('id', $fields)) !== false) {
                unset($fields[$key]);
                $fields = array_values($fields);
            }
            static::batchInsert($class, $fields, $insertData);
        }
    }

    /**
     * Массовая вставка
     * @see yii\db\QueryBuilder batchInsert function
     * 
     * @param string $class
     * @param array $fields
     * @param array $rows
     */
    public static function batchInsert($class, $fields, $rows)
    {
        $db = $class::getDb();
        $sql = $db->queryBuilder->batchInsert($class::tableName(), $fields, $rows);
        $db->createCommand($sql)->execute();
    }
    
    /**
     * Массовое обновление
     * @see yii\db\QueryBuilder batchInsert function
     * 
     * @param string $class
     * @param array $fields
     * @param array $rows
     * @param boolean $onConflictUpdate
     */
    public static function batchUpdate($class, $fields, $rows, $onConflictUpdate = true)
    {
        $primaryKeys = $class::primaryKey();
        $db = $class::getDb();
        $sql = $db->queryBuilder->batchInsert($class::tableName(), $fields, $rows);
        $primaryKeyString=implode(', ', $primaryKeys);
        $onConflictSql = 'DO NOTHING';
        if ($onConflictUpdate){
            $onConflictSql = ' DO UPDATE SET ';
            $updateSet = static::getOnUpdateSet($primaryKeys, $fields);
        }
        $db->createCommand($sql . " ON CONFLICT ({$primaryKeyString}) DO UPDATE SET {$updateSet}")->execute();
    }
    
    /**
     * Собираем строку для обновления при конфликте
     * @param array $primaryKeys
     * @param array $fields
     * @return string
     */
    private static function getOnUpdateSet($primaryKeys, $fields)
    {
        $result = "";
        foreach ($fields as $field) {
            if (in_array($field, $primaryKeys)){
                continue;
            }
            $result.=$field."=excluded.".$field.",";
        }
        $result = trim($result, ',');
        
        return $result;
    }
    
    /**
     * Првоерка на наличие первичных ключей в $row
     * @param array $primaryKeys
     * @param array $row
     * @return boolean
     */
    private static function checkRowOnPrimaryKeyExistence($primaryKeys, $row)
    {
        $result = false;
        foreach ($primaryKeys as $primaryKey) {
            if(isset($row[$primaryKey])){
                $result = true;
            }
        }
        
        return $result;
    }
    /**
     * ==================== BATCH BLOCK=========================================
     */
}
