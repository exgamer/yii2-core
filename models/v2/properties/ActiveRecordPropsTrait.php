<?php

namespace core\models\v2\properties;

/**
 * Трейт для моделей с дополнительными свойствами
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
trait ActiveRecordPropsTrait
{   
    public function attributes()
    {
        $a = parent::attributes();
        $column = $this->propertiesColumn();
        unset($a[$column]);
        
        return array_merge( $a, static::properties() );
    }
    
    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $data = parent::toArray($fields, $expand, $recursive);
        $column = $this->propertiesColumn();
        unset($a[$column]);
        
        return $data;
    }
}