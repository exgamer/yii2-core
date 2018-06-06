<?php
namespace core\replication\mongo\behaviors;

use yii\base\Behavior;
use yii\mongodb\ActiveRecord;
use core\helpers\DateHelper;
use MongoDB\BSON\UTCDateTime;

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
    public $getAsTimestamp = false;
    public $getAddHrs;
    
    public function events() 
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'setDate',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'setDate',
            ActiveRecord::EVENT_AFTER_FIND => 'getDate',
            ActiveRecord::EVENT_AFTER_INSERT => 'getDate',
            ActiveRecord::EVENT_AFTER_UPDATE => 'getDate',
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
                $this->getDte($attr);
                if ($this->getAsTimestamp){
                    $this->owner->{$attr} = strtotime($this->owner->{$attr});
                }
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
                $this->getDte($attr);
                $this->owner->{$attr} = DateHelper::getMongoDate($this->owner->{$attr}, $this->dateFormat);
            }
        }
    }
    
    /**
     * TODO
     * ЭТО костыль надо переделать
     * при редактировании записи идет к дате -6 часов
     * @param type $attr
     */
    private function getDte($attr)
    {
        if ($this->getAddHrs && $this->getAddHrs > 0){
            $sec = $this->getAddHrs * 60*60;
            $this->owner->{$attr} = date("Y-m-d H:i:s", strtotime ($this->owner->{$attr}) + $sec);
        }
    }
}

