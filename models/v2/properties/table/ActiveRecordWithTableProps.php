<?php
namespace core\models\v2\properties\table;

use core\models\ActiveRecord;

/**
 * AR модель с дополнительными свойствами, которые хранятся в дополнительной
 * таблице в горизонтальном виде
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
abstract class ActiveRecordWithTableProps extends ActiveRecord implements IARWithTableProps
{      
    use \core\models\v2\properties\table\ActiveRecordWithTablePropsTrait;
}