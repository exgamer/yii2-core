<?php
namespace core\behaviors;

use Yii;
use yii\base\Behavior;
use core\models\ActiveRecord;
use yii\helpers\ArrayHelper;
use core\interfaces\IBaseSearch;

/**
 * Поведение для полей являющихся массивом []
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
class ArrayFieldsBehavior extends Behavior
{
    /**
     * Массив атрибутов которые сохраняются массивы
     * 
     * @var array
     */
    public $attrs = [];
    
    public function events() 
    {
        $events =  [
            ActiveRecord::EVENT_BEFORE_INSERT => 'setValue',
            ActiveRecord::EVENT_AFTER_VALIDATE => 'setValue',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'setValue',
            ActiveRecord::EVENT_AFTER_FIND => 'getValue',
            ActiveRecord::EVENT_AFTER_INSERT => 'getValue',
            ActiveRecord::EVENT_AFTER_UPDATE => 'getValue',
        ];
        #для отключения события при валидации модели поиска
        #проблема с массивами постгреса - поисковый параметр массив преобразуется
        #в строку
        if($this->owner instanceof IBaseSearch) {
            unset($events[ActiveRecord::EVENT_AFTER_VALIDATE]);
        }
        
        return $events;
    }

    /**
     * После нахождения объекта преобразуем массив строки в объект
     */
    public function getValue()
    {
        if(empty($this->attrs)){
            return null;
        }
        foreach ($this->attrs as $attr) {
            if(! is_string($this->owner->{$attr}) || empty($this->owner->{$attr})){
                $this->owner->{$attr} = null;
                continue;
            }
            
            $this->owner->{$attr} = ArrayHelper::toPhpArray($this->owner->{$attr});
        }
    }
    
    /**
     * Перед сохранением преобразуем объект в строку
     */
    public function setValue()
    {
        if(empty($this->attrs) ){
            return null;
        }
        foreach ($this->attrs as $attr) {
            if (! is_array($this->owner->{$attr})){
                continue;
            }
            $values = array_filter(
                        $this->owner->{$attr} ,
                        function($v) {
                            return ! empty($v) ? $v : null;
                        }
            );
            if(! $values) {
                $this->owner->{$attr} = null;
                continue;
            }

            $this->owner->{$attr} = ArrayHelper::toPostgresArray($this->owner->{$attr});
        }
    }
}

