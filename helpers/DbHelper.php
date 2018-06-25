<?php
namespace core\helpers;

use \yii\db\Command;
use core\models\ActiveRecord;
use yii\helpers\Json;
use yii\base\Exception;
use core\interfaces\IBatch;
use console\components\migrations\MigrationExt;

/**
 * Вспомогательный класс содержащий полезные функции для работы с базой данных
 * 
 * @author CitizenZet <exgamer@live.ru>
 *
 */
class DbHelper 
{
    /**
     * Мапинг вставляемых моделей
     * 
     * @var array
     */
    public static $dataMap;
    protected static $errors = [];

    /**
     * ! Важно! если хотя бы в одной записи значение будет null поле для всех записей не будет в запросе
     * @param BaseActiveRecord model
     * $data = [
     *      'attribute'=>'value',
     *      ...
     * ]
     * @param array $data -данные для insert
     */
    public static function batchInsert(ActiveRecord $model, $data=[], $clearEmpty=true, $onUpdate=true)
    {
        if(! $model instanceof IBatch){
            throw new Exception('model must be instance of IBatch');
        }
        if (empty($data)){
            return false;
        }
        return $model::getDb()->transaction(function($db) use($model, $data, $clearEmpty, $onUpdate){
                #Получаем primary key 
                $primaryKeys = $model::primaryKey();
                #Получаем имя таблицы
                $table = $model::tableName();
                #Получаем схему таблицы
                $schema = $model::getTableSchema();
                #Получаем поля таблицы поностью
                $columnsFull=$schema->columns;
                #исключение атрибутов не входящих в мультивставку
                $model->excludeAttr($columnsFull);
                # Получаем только названия полей
                $columns= array_keys($columnsFull);
                #Получаем имя класса модели
                $modelClass = get_class($model);
                $updateValues=[];
                $values=[];
                
                foreach ($data as $attrs) {
                    $vs=[];
                    $validationModel = new $modelClass;
                    $validationModel->attributes =  $attrs;
                    $validationModel->scenario = ActiveRecord::SCENARIO_INSERT;
                    if (! $validationModel->validate()){
                        self::setError($validationModel->getErrors());
                        //print_r($validationModel->getErrors());
                        continue;
                    }
                    $insertAttr = $clearEmpty ? $model->clearEmptyAttr($validationModel->attributes):$validationModel->attributes;
                    foreach ($insertAttr as $attribute=>$value) {
                        /**
                         * если аттрибута нету в списке полей таблицы игнорим
                         */
                        if (!in_array($attribute, $columns)){
                            unset($insertAttr[$attribute]);
                            continue;
                        }
                        /**
                         * если аттрибут не входит в состав ключа то добавляем ег ов массив для обновления 
                         */
                        if (!in_array($attribute, $primaryKeys) && $value){
                            $updateValues[]=$attribute."=excluded.".$attribute;
                        }

                        $vs[]=self::validateValue($attribute, $value, $model);
                    }
                    $values[] = '(' . implode(', ', $vs) . ')';  
                    self::$dataMap[] = $attrs;
                }
                if (count($values) == 0 ){
                    return false;
                }
                //TODO поставить проверку если хотя бы одна модель не свалидировалась возвращать false чтобы можно было проверить выполнение батча
//                if (count($data)!=count($values)){
//                    return false;
//                }

                $insertColumns = array_keys($insertAttr);
                $insertColumns = self::encodeColumns($insertColumns);
                $columnsString = implode(', ', $insertColumns);
                $valuesString = implode(', ', $values);
                $primaryKeyString=implode(', ', $primaryKeys);
                $onConflictSql = 'DO NOTHING';
                $updateValues = array_unique($updateValues);
                if (count($updateValues)>0 && $onUpdate){
                    $onConflictSql = ' DO UPDATE SET ';
                    $onConflictSql .= implode(', ', $updateValues);
                    $onConflictSql = str_replace('group','"group"',$onConflictSql);
                }
                $sql  = "INSERT INTO {$table} ({$columnsString}) VALUES {$valuesString} ON CONFLICT ({$primaryKeyString}) $onConflictSql;";
                $db->createCommand($sql)->execute();
                
                $model->afterBatch(self::$dataMap);
                self::clearErrors();
                
                return true;
        });
    }
    
    /**
     * 
     * @param BaseActiveRecord model
     * $data = [
     *      'id'=>3,
     *      ...
     * ]
     * либо если составной
     * $data = [
     *      'primary_key_part_1'=>3,
     *      'primary_key_part_2'=>4,
     *      ...
     * ]
     * @param array $data -массив с primarykey записей
     */
    public static function batchDelete(ActiveRecord $model, $data=[])
    {
        if(! $model instanceof IBatch){
            throw new Exception('model must be instance of IBatch');
        }
        if (empty($data)){
           return false;
        }
        return $model::getDb()->transaction(function($db) use($model, $data){
                #Получаем primary key 
                $primaryKeys = $model::primaryKey();
                $params=[];
                $where=null;
                $i=1;
                #обрабатываем массив данных
                foreach ($data as $value) {
                    $dataArray=[];
                    #проверяем чтобы были переданы все поля из которых состоит primary key
                    foreach ($primaryKeys as $key) {
                        if (! isset($value[$key])){
                            throw new Exception("Не полностью передан ключ ".$key);
                        }
                        $dataArray[$key]=$value[$key];
                    }
                    #если хотя бы одно поле не передано данные в запрсо не попадают
                    if (count($primaryKeys)!=count($dataArray)){
                        continue;
                    }
                    $k=0;
                    $where.=($where?' OR ':'')."(";
                    foreach ($dataArray as $key=>$val) {
                        $where.=($k>0?" AND ":"").$key."=:yi".$i;
                        $params[':yi'.$i]=$val;
                        $i++;
                        $k++;
                    }
                    $where.=")";
                }
                $model::deleteAll($where,$params);
                self::$dataMap[] = $data;
                $model->afterBatch(self::$dataMap);
                
                return true;
        });
    } 
    
    /**
     * Возвращает ошибки
     * 
     * @return array
     */
    public static function getErrors()
    {
        return self::$errors;
    }
    
    /**
     * Установка ошибки
     * 
     * @param mixed $v
     */
    public static function setError($v)
    {
        self::$errors[] = $v;
    }
    
    /**
     * Очистка ошибок
     */
    protected static function clearErrors()
    {
        self::$errors = [];
    }
    
    /**
     * Проверяем значение и обрабатываем если необходимо для вставки в запрос
     * @param mixed $value
     * @return mixed
     */
    public static function validateValue($attribute, $value, $model)
    {       
        #Получаем схему таблицы
        $schema = $model::getTableSchema();
        #Получаем поля таблицы поностью
        $columnsFull=$schema->columns;
        if (! isset($columnsFull[$attribute]))
        {
            throw new Exception("Нет такого аттрибута ".$attribute);
        }
        $attr = $columnsFull[$attribute];
        #получаем пхп тип аттрибута 
        $type = 'string';
        if (isset($attr->phpType)){
            $type = $attr->phpType;
        }
        if ($value === null){
           
            $defaultValue = self::checkTableColumn($attr);
            if ($type=='integer')
            {
                return $defaultValue?$defaultValue:0;
            }
            if ($type=='boolean')
            {
                return $defaultValue?$defaultValue:"'f'";
            }
            return $defaultValue?$defaultValue:"NULL";
        }
        if (is_string($value)){
           return self::getStringValueForSql($value);
        }
        if (is_array($value)){
            $value = Json::encode($value);
            return self::getStringValueForSql($value);
        }
        
        return $value;
    }
    
    /**
     * Проверка поля из схемы БД на разрешение NULL и наличие дефолтного значения
     * @param type $column
     * @return type
     */
    public static function checkTableColumn($column)
    {
        $defaultValue=null;
        //if ($column->allowNull!=1){
            $defaultValue = $column->defaultValue;
        //}
        return $defaultValue;
    }


    /**
     * вернуть массив как json для вставки в запрос
     * @param array $value
     * @return string
     */
    public static function getArrayAsJsonString($value)
    {
            $value = Json::encode($value);
            return self::getStringValueForSql($value);
    }
    
    /**
     * вернуть строку как строку для вставки в запрос
     * @param string $string
     * @return string
     */
    public static function getStringValueForSql($string)
    {
        return "'".$string."'";
    }
    
    /**
     * @deprecated
     * Функция для citus data оборачивает запрос в функцию 
     * master_modify_multiple_shards
     * 
     * @param Command $command
     * @param ActiveRecord $model
     */
    public static function wrapSqlpMultipleShards(Command $command)
    {
        $rawSql = $command->rawSql;
        $sql = str_replace("'", "''", $rawSql);
        
        $command->setSql("SELECT master_modify_multiple_shards('{$sql}')");
    }
    
        
    /**
     * Выполнение sql из временого файла
     * 
     * @param string $tmp_file
     * @param type $sql
     */
    public static function executeSqlTmpFile($db, $tmp_file, $sql)
    {
        $f = fopen($tmp_file, 'w+');
        fwrite($f, $sql);
        fclose($f);
        #выполнение sql из файла
        $m = new MigrationExt();
        $m->db = $db;
        $m->executeFile($tmp_file, false);
        #удаление веременного файла
        unlink($tmp_file);
    }
    
    /**
     * Возвращает название таблицы без схемы
     * 
     * @param string $name
     * @return string
     */
    public static function getTableNameWithOutSchemas($name)
    {
        $parts = explode(".", $name);
        if(! $parts || !is_array($parts)){
            return null;
        }
        
        return end($parts);
    }

    /**
     * Обработка колонок добавление двойных ковычек
     * для избежания ошибок в запросе при использовании
     * зарезервированных слов
     * 
     * @param array $columns
     */
    protected static function encodeColumns($columns)
    {
        $result = [];
        if(! $columns || !is_array($columns)){
            return null;
        }
        foreach ($columns as $value) {
            $result[] = "\"{$value}\"";
        }
        
        return $result;
    }
}