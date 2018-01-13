<?php
namespace core\models;

use core\traits\RemoteBaseActiveRecordTrait;

/**
 * базовая модель для данных которые частично хранятся на удаленном серваке по значению определенного аттрибута
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class SplittedBaseActiveRecord extends RemoteBaseActiveRecord
{
    use RemoteBaseActiveRecordTrait;
}
