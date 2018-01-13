<?php
namespace core\models;

use core\models\ActiveRecord;
use core\traits\RemoteBaseActiveRecordTrait;

/**
 * базовая модель для данных которые частично хранятся на удаленном серваке
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class RemoteBaseActiveRecord extends ActiveRecord
{
    use RemoteBaseActiveRecordTrait;
}