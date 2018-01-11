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
     * Возвращает метку состояния объекта
     * 
     * @return string
     */
    public function getStatusLabel()
    {
        $ar = self::getStatusArray();
        if(! isset($ar[$this->status])){
            return null;
        }
        
        return $ar[$this->status];
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
}