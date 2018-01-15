<?php
namespace core\traits;

use Yii;
use core\helpers\ConstHelper;
use core\models\ActiveRecord;
/**
 * содержит правила дял валидации станлдартных полей
 */
trait StandardRulesTrait
{
    public static function statusFieldRules()
    {
        return [
                    [
                        'status',
                        'integer'
                    ],
                    [
                        'status',
                        'default',
                        'value' => ConstHelper::STATUS_ACTIVE,
                        'on'=>[
                            ActiveRecord::SCENARIO_DEFAULT,
                            ActiveRecord::SCENARIO_INSERT
                        ]
                    ],
        ];
    }
    
    public static function createTsFieldRules()
    {
        return [
            ['create_ts', 'date','format'=>'php:Y-m-d H:i:s'],
            ['create_ts','default','value' =>date("Y-m-d H:i:s"),'on'=>[self::SCENARIO_DEFAULT,self::SCENARIO_INSERT]],
        ];
    }
    
    public static function isDeletedFieldRules()
    {
        return [
           ['is_deleted','boolean'],
        ];
    }
      
    /**
     * Возвращает метку состояние записи
     * 
     * @return string
     */
    public function getStatusLabel()
    {
        return self::getLabelByArray($this->status, 'getStatusArray');
    }
    
    /**
     * Возвращает метку состояния удаления записи
     * 
     * @return string
     */
    public function getIsDeletedLabel()
    {
        return self::getLabelByArray($this->is_deleted, 'getBooleanArray');
    }

    /**
     * Возвращает метку состояния объекта
     * 
     * @param string $key  - ключ массива
     * @param string $array_fn  - функция возврата массива
     * 
     * @return string
     */
    private static function getLabelByArray($key, $array_fn)
    {
        $list = self::{$array_fn}();
        if(! isset($list[$key])){
            return null;
        }
        
        return $list[$key];
    }
    
    /**
     * Список статусов
     * 
     * @return array
     */
    public static function getStatusArray()
    {
        return [
            ConstHelper::STATUS_CREATED => Yii::t('common', 'Новый'),
            ConstHelper::STATUS_ACTIVE => Yii::t('common', 'Активный'),
            ConstHelper::STATUS_LOCKED => Yii::t('common', 'Заблокированый'),
        ];
    }
    
    /**
     * Массив буленовских значений
     * 
     * @return array
     */
    public static function getBooleanArray()
    {
        return [
            0 => Yii::t('common', 'Нет'),
            1 => Yii::t('common', 'Да'),
        ];
    }
}