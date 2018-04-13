<?php

namespace core\traits;

use Yii;
use core\models\ActiveRecord;

/**
 * @todo Эксперементальный режим
 * 
 * Автоматический поиск по часто используемому набору атрибутов
 * расширяет ActiveQuery
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
trait QuerySearchSetTrait 
{
    /**
     * Набор атрибутов
     * 
     * @var array
     */
    public $composition = [
        'id',
        'status',
        'is_deleted',
        'caption' => [
            'compareByLanguage',
            'true',
            'true'
        ],
    ];
    
    /**
     * Добавление элементов в набор
     * 
     * @param string | array $value
     * @param string $attribute
     * 
     * ```php
     * Подмена анонимной функцией
     * $custom = [
     *     function($q, $attribute, $out, $out2 , .....) {
     *         $q->andWhere([$attribute => 1]);
     *         if($out) {
     *             echo 'Вывод';
     *         }
     *         if($out2) {
     *             echo 'Вывод еще раз';
     *         }
     *     },
     *     true
     * ];
     * $query->pushComposition($custom, 'id');
     * Подмена функцией класса
     * $custom = [
     *       'compareByLanguage',
     *       'true',
     *       'true'
     * ];
     * $query->pushComposition($custom, 'id');
     * Докидывает в набор
     * $query->pushComposition('id');
     * ```
     */
    public function pushComposition($value, $attribute = null)
    {
        if(! $attribute) {
            $this->composition[] = $value;
            return;
        }
        if(isset($this->composition[$attribute])) {
            $this->composition[$attribute] = $value;
            return;
        }
        $index = array_search ($attribute, $this->composition);
        if($index !== false) {
            unset($this->composition[$index]);
        }
        $this->composition[$attribute] = $value;
    }

    /**
     * Отключение атрибутов в наборе
     * Если атрибут не передается отключается весь набор
     * Атрибуты для отключения передаются в функцию параметрами
     * 
     * @param string $attribute
     * @return type
     */
    public function disableComposition($attribute = null)
    {
        if(! $attribute) {
            $this->composition = null;
            return;
        }
        $arguments = func_get_args();
        foreach ($arguments as $argument) {
            $this->removeComposition($argument);
        }
    }
    
    /**
     * Удаление атрибута из набора для поиска
     * 
     * @param string $attribute
     */
    private function removeComposition($attribute)
    {
        if(isset($this->composition[$attribute])) {
            unset($this->composition[$attribute]);
        } else {
            $index = array_search ($attribute, $this->composition);
            if($index === null) {
                return;
            }
            unset($this->composition[$index]);
        }
    }

    /**
     * Автоатический поск по набору
     * 
     * @param ActiveRecord $model
     */
    public function applyCompostition( ActiveRecord $model )
    {
        if(! $this->composition || ! is_array($this->composition)) {
            return ;
        }
        foreach ($this->composition as $key => $value) {
            #ключ строка (атрибут - ключ)
            if(is_string($key)) {
                if(! $model->hasAttribute($key) || ! $model->{$key}) {
                    continue;
                }
                $tableName = $model::tableName();
                #значение массив настроек
                if(is_array($value)) {
                    $fn = array_shift($value);
                    if(! is_callable($fn)) {
                        array_unshift($value, $model, $key);
                        call_user_func_array([$this, $fn], $value);
                    } else {
                        array_unshift($value, $this, $key);
                        call_user_func_array($fn, $value);
                    }
                } else {
                    #значение простая функция трейта
                    $fn = $value;
                    $fn($key, $tableName);
                }
            } else {
                #ключ целочисленный (атрибут - значение)
                if(! $model->hasAttribute($value)) {
                    continue;
                }
                $this->andFilterWhere([$value => $model->{$value}]);
            }
        }
    }
    
    /**
     * Поиск по языку в мультиязычный jsonb полях
     * 
     * @param ActiveRecord $model
     * @param string $attr
     * @param bolean $lower
     * @param bolean $like
     * @return type
     */
    public function compareByLanguage($model, $attr, $lower = true, $like = true)
    {
        $language = Yii::$app->language;
        $operator = '=';
        if($like) {
            $operator = 'like';
        }
        $this->setJsonbCondition($model, $language, $lower, $operator, $attr);
    }
    
    /**
     * Добавить в запрос поиск по jsonb полю
     * 
     * ПРИМЕР поиска по jsonb с вложенными массивами
     * $query->setJsonbCondition($this, ['key_1','key_2','final_key']);
     * поиск с 1 вложенностью
     * $query->setJsonbCondition($this, 'final_key');
     * 
     * @param ActiveRecord $model
     * @param string||string[] $attr
     * @param string $operator
     * @param string $jsonbFieldName
     */
    public function setJsonbCondition($model, $attrs, $lower = false, $operator = '=', $jsonbFieldName = 'properties')
    {
        $tableName = $model::tableName();
        $fn = null;
        if($lower) {
            $fn = 'lower';
        }
        $jsonPath = null;
        if (is_string($attrs)){
              $attrs = [$attrs];
        }
        $attr = null;
        $attrsCount = count($attrs);
        $i = 1;
        foreach ($attrs as $ind => $key) {
            if ($i == $attrsCount){
                $attr = $key;
            }
            if ($i < $attrsCount){
                $jsonPath.=" -> '{$key}'";
            }else{
                $jsonPath.=" ->> '{$key}'";
                continue;
            }
            $i++;
        }
        #если у модели нет аттрибута значит заменяем его на имя поля
        if (! property_exists($model, $attr) && $jsonbFieldName !== 'properties'){
            $attr = $jsonbFieldName;
        }
        if ($model->{$attr} !== null){
            if ($lower){
                $model->{$attr} = mb_strtolower($model->{$attr});
            }
            $this->andWhere("{$fn}({$tableName}.{$jsonbFieldName} {$jsonPath}) {$operator} :PARAM",[
                ':PARAM' => $operator == 'like'?'%'.$model->{$attr}.'%':$model->{$attr}
            ]);
        }
    }
}