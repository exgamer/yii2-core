<?php
namespace core\behaviors;

use Yii;
use yii\base\Behavior;
use core\models\ActiveRecord;

/**
 * Поведение для полей являющихся Json строкой
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
class JsonFieldsBehavior extends Behavior
{
    /**
     * Массив атрибутов которые сохраняются json
     * 
     * @var array
     */
    public $jsonAttr = [];
    
    public function events() 
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'setJson',
            ActiveRecord::EVENT_AFTER_VALIDATE => 'setJson',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'setJson',
            ActiveRecord::EVENT_AFTER_FIND => 'getJson',
            ActiveRecord::EVENT_AFTER_INSERT=>'getJson',
            ActiveRecord::EVENT_AFTER_UPDATE=>'getJson',
        ];
    }

    /**
     * После нахождения объекта преобразуем json строки в объект
     */
    public function getJson()
    {
        if(empty($this->jsonAttr)){
            return null;
        }
        foreach ($this->jsonAttr as $attr) {
            if(! is_string($this->owner->{$attr})){
                continue;
            }
            
            $this->owner->{$attr} = json_decode($this->owner->{$attr}, true) ? json_decode($this->owner->{$attr}, true) : [];
        }
    }
    
    /**
     * Перед сохранением преобразуем объект в json строку
     */
    public function setJson()
    {
        if(empty($this->jsonAttr) ){
            return null;
        }
        foreach ($this->jsonAttr as $attr) {
            if (!is_array($this->owner->{$attr})){
                continue;
            }
            $this->owner->{$attr} = json_encode($this->owner->{$attr});
        }
    }
    
    /**
     * Получить содержимое поля по ключу
     */
    public function getJsonFieldByKey($model, $attr)
    {
        if (!is_array($model->{$attr})){
            return $model->{$attr};
        }
        return  isset($model->{$attr}[Yii::$app->languageDetector->getIso()])?$model->{$attr}[Yii::$app->languageDetector->getIso()]:reset($model->{$attr});
    }
}

