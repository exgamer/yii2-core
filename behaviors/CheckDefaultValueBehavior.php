<?php
namespace core\behaviors;

use yii\base\Behavior;
use core\models\ActiveRecord;

/**
 * Поведение для полей с дефолтным значением
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
class CheckDefaultValueBehavior extends Behavior
{
    /**
     * Массив атрибутов
     * 
     * @var array
     */
    public $attrs = [];
    
    public function events() 
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'checkValue',
            ActiveRecord::EVENT_AFTER_VALIDATE => 'checkValue',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'checkValue'
        ];
    }

    /**
     * Перед сохранением првоеряем если аттрибут пуст то подставляем дефолт
     */
    public function checkValue()
    {
        if(empty($this->attrs) ){
            return null;
        }
        foreach ($this->attrs as $attr) {
            echo $this->owner->{$attr['attribute']};
            $this->owner->{$attr['attribute']} = $this->owner->{$attr['attribute']}?$this->owner->{$attr['attribute']}:$attr['default'];
        }
    }
}

