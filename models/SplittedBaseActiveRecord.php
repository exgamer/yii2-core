<?php
namespace core\models;

use core\models\RemoteBaseActiveRecord;

/**
 * базовая модель для данных которые частично хранятся на удаленном серваке по значению определенного аттрибута
 * 
 * вот по значению этого аттрибута делим данные
 * @property string $splitAttributeName
 * если true пишем данные в обе базы
 * @property string $doubleWrite
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class SplittedBaseActiveRecord extends RemoteBaseActiveRecord
{
    public $splitAttributeName = 'name';
    public $doubleWrite = false;
    
    /**
     * @see yii\db\BaseActiveRecord
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if (!in_array($this->{$this->splitAttributeName}, $this->remoteDataValues()) && $this->doubleWrite == false){
            return $this->clearSave($runValidation, $attributeNames);
        }
        if ($this->doubleWrite){
            if (! $this->clearSave($runValidation, $attributeNames))
            {
                return false;
            }
        }
        if (! $this->initCommunicator($runValidation, $attributeNames)) {
            return false;
        }
        $this->setBackRemoteData();

        return true;
    }
    
    /**
     * @see \common\models\base\RemoteBaseActiveRecord
     */
    public function getRemoteModelFieldsMap()
    {
        return array_keys($this->attributes);
    }
    
    /**
     * Можно указать аттрибуты по значениям которых будем разбивать данные
     * К примеру записи где name будет в массиве попадут на удаленный ресурс
     * [
     *      'name'=>[
     *          'ГОЛОВА',
     *          'ЖОПА',
     *      ]
     * ]
     * @return  array
     */
    abstract function remoteDataValues();  
}
