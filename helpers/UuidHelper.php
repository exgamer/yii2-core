<?php
namespace core\helpers;

use thamtech\uuid\helpers\UuidHelper as Base;
use Ramsey\Uuid\Uuid;
use core\validators\UuidValidator;

/**
 * Класс предоставляет функции UUID, которые вы можете использовать в своем приложении
 * 
 * @author Kamaealkz
 */
class UuidHelper extends Base
{
    private static $_node = 999999999;
    
    /**
     * @override thamtech\uuid\helpers\UuidHelper
     */
    public static function uuid()
    {
        return Uuid::uuid1(self::$_node)->toString();
    }
    
    /**
     * @override thamtech\uuid\helpers\UuidHelper
     */
    public static function isValid($uuid)
    {
        $validator = new UuidValidator();

        return $validator->validate($uuid, $error);
    }
}

