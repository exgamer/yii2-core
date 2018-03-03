<?php
namespace core\models\v2\properties\column;

use core\models\ActiveRecord;

/**
 * AR модель с дополнительными свойствами, которые хранятся в дополнительной
 * колонке в формате JSON (колонка jsonb)
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
abstract class ActiveRecordWithColumnProps extends ActiveRecord implements IARWithColumnProps
{      
    use \core\models\v2\properties\column\ActiveRecordWithColumnPropsTrait;
}