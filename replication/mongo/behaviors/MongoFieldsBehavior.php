<?php
namespace core\replication\mongo\behaviors;

use yii\base\Behavior;
use yii\mongodb\ActiveRecord;
use core\helpers\DateHelper;

/**
 * Поведение для полей даты записей из mongo
 * 
 * @author kamaelkz <i.skiba@luxystech.com>
 */
class MongoFieldsBehavior extends Behavior
{
    /**
     * Массив атрибутов которые сохраняются под типом MongoDate
     * 
     * @var array
     */
    public $dateAttr = [];
    public $dateFormat = 'Y-m-d';
    
    public function events() 
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'setDate',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'setDate',
            ActiveRecord::EVENT_AFTER_FIND => 'getDate'
        ];
    }

    /**
     * После нахождения объекта преобразуем атрибуты MongoDate в обычную дату
     */
    public function getDate()
    {
        if(! empty($this->dateAttr)){
            foreach ($this->dateAttr as $attr) {
                if(! is_object($this->owner->{$attr})){
                    continue;
                }
                $this->owner->{$attr} = date($this->dateFormat, $this->owner->{$attr}->toDateTime()->getTimestamp());
                $this->owner->{$attr} = strtotime($this->owner->{$attr});
            }
        }
        
    }
    
    /**
     * Перед сохранением преобразуем дату в объект MongoDate
     */
    public function setDate()
    {
        if(empty($this->dateAttr) ){
            return null;
        }
        foreach ($this->dateAttr as $attr) {
            if(! $this->owner->{$attr}){
                    $this->owner->{$attr} = new UTCDateTime (( new \DateTime (date($this->dateFormat))) );
            } else  if(is_string($this->owner->{$attr})){
                    $this->owner->{$attr} = DateHelper::getMongoDate($this->owner->{$attr}, $this->dateFormat);
            }
        }
    }
}

