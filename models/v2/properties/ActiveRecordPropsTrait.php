<?php

namespace core\models\v2\properties;

use yii\helpers\ArrayHelper;

/**
 * Трейт для моделей с дополнительными свойствами
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
trait ActiveRecordPropsTrait
{   
    public function getAttributes($names = null, $except = []) 
    {
        $attributes = parent::getAttributes($names, $except);
        $properties = static::properties();
        if(! is_array($properties)){
            return $attributes;
        }
        foreach ($properties as $property) {
            $attributes[$property] = $this->{$property};
        }

        return $attributes;
    }
    
    public function hasAttribute($name) 
    {
        $attributes = $this->attributes();
        $properties = static::properties();
        $merge = ArrayHelper::merge($attributes, $properties);
        $result = array_flip($merge);
        
        return isset($result[$name]);
    }
}

