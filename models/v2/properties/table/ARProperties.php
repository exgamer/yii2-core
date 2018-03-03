<?php

namespace core\models\v2\properties\table;

use core\models\ActiveRecord;

/**
 * Класс для дополнительный свойств основной модели ARWithProps
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
abstract class ARProperties extends ActiveRecord implements \core\interfaces\IBatch
{
    /**
     * Полный путь до основной модели, которую дополняет
     * AR::class
     * 
     * @return string
     */
    abstract static function getPrimaryClass();
    
    
    public function afterBatch($data) 
    {
        
    }
}

