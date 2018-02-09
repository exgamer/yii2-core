<?php

namespace core\models;

use core\interfaces\Filterable;
use core\queries\ActiveQuery;
use core\helpers\StringHelper;
use Yii;

/**
 * Базовая модель.
 * Class ActiveRecord
 */
class ActiveRecord extends \yii\db\ActiveRecord implements Filterable
{

    const STATE_DELETED = 3;

    const FILTER_SCENARIO = "filter";
    const FILTER_ONE_SCENARIO = "filter_one";

    const SCENARIO_INSERT = 'insert';
    const SCENARIO_UPDATE = 'update';
    
    const SCRNARIO_EXCEL_IMPORT = 'excel_import';
    
    const SCENARIO_TRANSFER = 'transfer';
    
    /**
     * Масссив для расширения полей возврата из ответа
     * получается из ActiveQuery
     * 
     * @var array
     */
    protected $extendModelFieldsMap = [];

    /**
     * Утанавливает расширяющие поля
     * 
     * @param array $array
     */
    public function setExtendModelFieldsMap($array)
    {
        $this->extendModelFieldsMap = $array;
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
    
    public function scenarios()
    {

        $scenarios = array_merge(parent::scenarios(),
            [
                self::SCENARIO_INSERT => $this->attributesForSave(self::SCENARIO_INSERT),
                self::SCENARIO_UPDATE => $this->attributesForSave(self::SCENARIO_UPDATE),
                self::FILTER_SCENARIO => $this->filterAttributes(),
                self::FILTER_ONE_SCENARIO => $this->filterOneAttributes(),
                self::SCENARIO_TRANSFER => $this->attributesForSave(self::SCENARIO_TRANSFER)
            ]);
        return $scenarios;

    }

    public function attributesForSave($scenario)
    {
        return $this->attributes();
    }

    public function filterAttributes()
    {
        return [];
    }

    public function filterOneAttributes()
    {
        return [];
    }

    /**
     * На запросы для всех моделей используется ActiveQuery класс,
     * Если вы хотите свои скопсы, то создайте класс в папке queries, наследуйте его от \app\components\ActiveQuery и
     * в своей модели переопределите метод find()
     * @return ActiveQuery
     */
    public static function find()
    {
        $queryClass = static::getBaseActiveQueryClass();
        $query = new $queryClass(get_called_class());
        static::queryExtend($query);
        
        return $query;
    }

    /**
     * Действия перед запросом
     * @param AQ $query
     */
    public static function queryExtend($query)
    {
        
    }
    
    /**
     * 
     * @return string
     */
    public static function getBaseActiveQueryClass()
    {
        return 'core\queries\BaseActiveQuery';
    }
    
    /**
     * Проверяем на наличие стандартных колонок и записываем значения по дефолту
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (in_array("ts", $this->attributes()) AND $this->isNewRecord AND empty($this->ts)) {
            $this->ts = time();
        }

        if (in_array("info", $this->attributes()) AND is_array($this->info)) {
            $this->info = json_encode($this->info);
        }

        if (in_array("state", $this->attributes()) AND $this->isNewRecord AND $this->state === null) {
            $this->state = 1;
        }

        return parent::beforeSave($insert);
    }
    
    /**
     * DEPRECATED - исользуйте метод fields
     * Возвращает аттрибуты модели в виде массива
     * @param mixed $models - модель
     * @param array $relations - если нужно обработать связи
     * @return array
     */
    public static function arrayAttributes($models, $relations = [], $fields = [], $allowEmpty = false)
    {

        if (is_array($models)) {
            $result = [];
            foreach ($models as $k=>$m) {

                if (empty($fields)) {
                    $fields = $m->fields();
                }

                $attr = [];
                foreach ($fields as $f) {
                    if (method_exists($m, $f)) {
                        $attr[$f] = $m->$f();
                    } else $attr[$f] = $m->$f;
                }

                foreach ($attr as $name=>$value) {
                    if (!empty($value) OR $allowEmpty) {
                        $result[$k][$name] = is_numeric($value) ? (($value == (int) $value) ? (int) $value : (float) $value) : $value;
                    }
                }
                if (!empty($relations))
                {
                    foreach ($relations as $r=>$v) {
                        if (!is_array($v)) {
                            if (is_object($m->$v) OR is_array($m->$v)) {
                                $result[$k][$v] = self::arrayAttributes($m->$v);
                            } else {
                                $result[$k][$v] = $m->$v;
                            }
                        } else {

                            $_rels = [];
                            if (isset($v['relations'])) {
                                $_rels = $v['relations'];
                            }
                            $r_fields = [];
                            if (isset($v['fields'])) {
                                $r_fields = $v['fields'];
                            }

                            if (!isset($v['relations']) AND !isset($v['fields'])) {
                                $_rels = $v;
                            }

                            $result[$k][$r] = self::arrayAttributes($m->$r, $_rels, $r_fields);
                        }
                    }
                }
            }
            
        } else {

            if (empty($fields)) {
                $fields = $models->fields();
            }

            $attr = [];
            foreach ($fields as $f) {
                if (method_exists($models, $f)) {
                    $attr[$f] = $models->$f();
                } else $attr[$f] = $models->$f;
            }

            foreach ($attr as $name=>$value) {
                if (!empty($value) OR $allowEmpty) {
                    $result[$name] = is_numeric($value) ? (($value == (int) $value) ? (int) $value : (float) $value) : $value;
                }
            }
            if (!empty($relations))
            {
                foreach ($relations as $r=>$v) {
                    if (!is_array($v)) {
                        if (is_object($models->$v) OR is_array($models->$v)) {
                            $result[$v] = self::arrayAttributes($models->$v);
                        } else {
                            $result[$v] = $models->$v;
                        }
                    } else {

                        $_rels = [];
                        if (isset($v['relations'])) {
                            $_rels = $v['relations'];
                        }
                        $r_fields = [];
                        if (isset($v['fields'])) {
                            $r_fields = $v['fields'];
                        }

                        if (!isset($v['relations']) AND !isset($v['fields'])) {
                            $_rels = $v;
                        }

                        $result[$r] = self::arrayAttributes($models->$r, $_rels, $r_fields);
                    }
                }
            }
        }
        return $result;
    }

    public function translate($attribute)
    {
        $t = json_decode($attribute, true);
        if (is_array($t)) {
            if (isset($t[Yii::$app->language])) {
                return $t[Yii::$app->language];
            } else if (isset($t['ru'])) {
                return $t['ru'];
            }
        }
        return $attribute;
    }


    public static function autoComplete($attribute, $query)
    {
        $data = static::find()->filterWhere(["like", $attribute, $query])
            ->distinct(true)->all();

        $result = [
            "query"=>$query,
            "suggestions"=>[]
        ];
        if (!empty($data)) {
            foreach($data as $d) {
                $result['suggestions'][] = $d->{$attribute};
            }
        }
        return $result;
    }

    /**
     * Если колонка в таблице хранится как json строка, то выборка $this->{column}Json - вернет массив
     * @param string $name
     * @return array|mixed
     */
    public function __get($name) {
        if (substr($name, strlen($name) - 4, 4) == "Json") {
            $name = substr($name,0,strlen($name)-4);
            $attr = parent::__get($name);
            return is_array($attr) ? $attr : (json_decode($attr, true) ? json_decode($attr, true) : []);
        }
        return parent::__get($name);
    }

    public function info()
    {
        return [];
    }

    /**
     * Обработка входящих параметров запроса
     * Добавляем в запрос аттрибуты? которые не являются объявленными переменными и которые указаны в методе filterAttributes()
     * @param type $query
     */
    public function applyFilter(&$query)
    {
        $variables = get_object_vars($this);
        $except = array_keys($variables);
        $include = $this->filterAttributes();
        foreach ($this->attributes as $key => $value) {
            if ($value == null || $value == "" || in_array($key, $except)){
                continue;
            }
            if (! in_array($key, $include)){
                continue;
            }
            $query->andWhere([
                $query->alias . "." . $key => $value
            ]);
        }
    }

    public function applyFilterOne(&$query)
    {

    }
    
    /**
     * получаем связь и закидываем ее в переменную с установкой своего ключа
     * @param string $attribute_name - переменная в которую закидываем связь
     * @param string $field_name - поле которое будет ключем
     * @param array $records - записи связи
     * @param boolean $is_multi - содержит ли ключ вложенные массивы или один массив 
     */
    public function relationToCustomField($attribute_name, $field_name, $records, $is_multi=false)
    {
        $this->{$attribute_name}=[];
        foreach($records as $r){
                if ($is_multi){
                    $this->{$attribute_name}[$r->{$field_name}][]=$r;
                }else{
                    $this->{$attribute_name}[$r->{$field_name}]=$r;
                }
        }
    }
    
    /**
     * Превращает связь в ассоциативный массив где одно поле будет ключом а другое значением
     * @param type $attribute_name - переменная в которую закидываем связь
     * @param type $key_field_name - поле которое будет ключем
     * @param type $value_field_name - поле которое будет значением
     * @param type $records - записи связи
     * @param type $is_multi - содержит ли ключ вложенные массивы или один массив 
     */
    public function relationToAssocArray($attribute_name, $key_field_name,$value_field_name, $records, $is_multi=false , &$item = null)
    {
        $this->{$attribute_name}=[];
        foreach($records as $r){
                /**
                 * Приводим массив к объекту чтобы вытащить данные
                 */
                $r=(object)$r;
                if ($is_multi){
                    $this->{$attribute_name}[$r->{$key_field_name}][]=$r->{$value_field_name};
                }else{
                    $this->{$attribute_name}[$r->{$key_field_name}]=$r->{$value_field_name};
                }
        }
        $item = $this->{$attribute_name};
    }
    
    /**
     * Возвращает транслит заголовка
     * 
     * @param mixed $field
     * @return string
     */
    public function getTranslitCaption()
    {
        $result = $field = $this->{CAPTION_FIELD};
        if(is_array($field)){
            $result = isset($field['ru']) ? $field['ru'] : reset($field);
        }
        
        return StringHelper::translit($result);
    }
    
    /**
     * @see common\interfaces\IBatch
     * @return array
     */
    public function excludeAttrMap()
    {
        return [
        ];
    }
    
    /**
     * @see common\interfaces\IBatch
     * @param array $columns
     * @return mixed
     */
    public function excludeAttr(&$columns)
    {
        if(! $columns || !is_array($columns)){
            return null;
        }
        $excludeMap = $this->excludeAttrMap();
        foreach ($excludeMap as $item) {
            if (isset($columns[$item])){
                unset($columns[$item]);
            }
        }
    }
    
    /**
     * @see common\interfaces\IBatch
     * @param array $attributes
     * @return array
     */
    public function clearEmptyAttr($attributes)
    {
            return  array_filter (
                            $attributes ,
                            function($key){
                                    if($key !== null && $key !== ""){
                                            return true;
                                    }
                            }
            );
    }

    
    /**
     * метод для валидации полей которые могут прилететь в виде массива? строки которая разделена запятой или просто значение
     * например person_id может быть просто значение, может быть массивом значений или person_id,person_id,person_id
     * @param type $attribute
     * @param type $params
     * @return boolean
     */
    public function validateVariativeField($attribute, $params)
    {
        $validatorClass = $params['validatorClass'];
        $validator = new $validatorClass();
        if (isset($params['integerOnly'])){
            $validator->integerOnly=true;
        }
        if (is_array($this->{$attribute})){
            foreach ($this->{$attribute} as $val) {
                if (! $validator->validate($val)){
                    $this->addError($attribute,Yii::t('api', "Неверное значение = ".$val));
                    return false;
                }
            }
            
            return true;
        }
        
        if(stristr($this->{$attribute}, ',')){
            $result = null;
            $array= explode(',', $this->{$attribute});
            foreach ($array as $value) {
                if (! $validator->validate($value)){
                    $this->addError($attribute,Yii::t('api', "Неверное значение = ".$val));
                    return false;
                }
                
                $result[] = $value;
            }
            $this->{$attribute} = $result;
            
            return true;
        }
        


        if (! $validator->validate($this->{$attribute})){
            $this->addError($attribute,Yii::t('api', "Неверное значение = ".$this->{$attribute}));
            return false;
        }

        return true;
    }
    
    public static function beforeFindModel(){}
    public static function afterFindModel(){}
}
