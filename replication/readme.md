/**
 * Создание модели с дублированием в mongo
 *
 * @author CitizenZet <exgamer@live.ru>
/**

/**
 * NOTE
 * У ReplicatedActiveRecordWithProps и ReplicatedActiveRecord есть признак $onlyReplica. который по умолчанию false
 * от него зависит будет ли вестись запись в основную БД.
 * Добавлен для быстрого перехода только на реплику
 *
/**

1. Создаем класс модели описывающий документ для mongo и наследуем его от common\replication\mongo\models\base\BaseStatisticMongoActiveRecord и указываем связанную модель из пункта 3
=========================Пример=================================================
<?php
namespace common\replication\mongo\models\base;

use core\replication\mongo\models\BaseRelatedMongoActiveRecord;

/**
 * base replication mongo model for Statistic
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class BaseStatisticMongoActiveRecord extends BaseRelatedMongoActiveRecord
{
    public function behaviors()
    {
        return [
            'MongoFieldsBehavior' => [
                    'class' => 'core\replication\mongo\behaviors\MongoFieldsBehavior',
                    'dateAttr' => [
                        'date'
                    ],
                    'dateFormat' => 'Y-m-d H:i:s',
                    'getAddHrs' => 6
            ]
        ];
    }
}
================================================================================

2. Создаем класс обработчик и наследуем его от core\replication\mongo\handlers\BaseMongoReplicationHandler и указываем mongo модель
3. Создаем класс модели и наследуем его от common\models\analytics\base\Statistic и указываем класс обработчик
    3.1 В классе создать необходимые свойства
==========================Пример================================================
<?php
namespace common\models\analytics\base;

use common\models\analytics\base\StatisticProperties;
use core\replication\base\models\ReplicatedActiveRecordWithProps;

/**
 * Базовая модель для статистических данных
 * 
 * @property integer id
 * @property string name
 * @property integer parent_id
 * @property integer type из справочника StatisticTypes
 * @property date date
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class Statistic extends ReplicatedActiveRecordWithProps
{
    public static function  tableName()
    {
            return 'analytics.statistic';
    }

    public function rules()
    {
            return [
                    [
                        [
                            'name'
                        ],
                        'required'
                    ],
                    [
                        [
                            'parent_id',
                            'type',
                        ], 
                        'integer', 
                    ],
                    [
                        [
                            'name'
                        ], 
                        'string', 
                        'max' => 255
                    ],
                    [
                        [
                            'date'
                        ], 
                        'date', 
                        'format' => 'php:Y-m-d'
                    ]
            ];
    }

    /**
     * @see \core\models\TransactionalActiveRecord
     */
    public function beforeSaveModel()
    {
        if (! $this->name){
            $className = \yii\helpers\StringHelper::basename(get_class($this));
            $this->name = strtolower($className);
        }
    }
    
    /**
     * @see \core\models\base\ActiveRecordWithProps
     */
    public function getPropertyModel()
    {
        return new StatisticProperties();
    }
}

================================================================================

=========================Пример модели свойств==================================
<?php
namespace common\models\analytics\base;

use core\models\AProperty;

/**
 * properties для Statistic
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
class StatisticProperties extends AProperty
{
	public static function  tableName()
        {
		return 'analytics.statistic_properties';
	}
}

================================================================================

После при вызове основной модели наследованной от common\models\analytics\base\Statistic при сохранении данные бдут дублироваться в mongo



===============Пример основной модели=========================================== 
<?php
namespace common\models\analytics\organization;

use common\models\analytics\base\Statistic;

use common\replication\mongo\handlers\organization\InstitutionStatisticHandler;

/**
 * Модель описывающая данные по заведению
 * 
 * @property array $caption                - название в виде json ( пример {"en": "Английский", "ru": "Русский"} )
 * @property integer $country_id            - идентификатор страны
 * @property integer $city_id               - идентификатор города @property timestamp $create_ts           - дата создания записи
 * @property integer $institution_type                  - тип
 * @property integer $economic_type         - вид организации (ConstHelper::INSTITUTION_ORG_TYPE_<*>)
 * @property integer $foundation_year       - дата основания
 * 
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
class Institution extends Statistic
{
    public $country_id;
    public $city_id;
    public $institution_type;
    public $economyc_type;
    public $foundation_year;
    public $caption;
    public $language;

    public function rules()
    {
            return array_merge( parent::rules(),
                                [
                                        [
                                            [
                                                'country_id',
                                                'city_id',
                                                'institution_type',
                                                'economyc_type',
                                                'foundation_year'
                                            ], 
                                            'integer', 
                                        ],
                                        [
                                            [
                                               'caption',
                                               'language'
                                            ],
                                           'safe'
                                        ]
                                ]
            );
    }
    
    public function behaviors()
    {
        return [
            'JsonFieldsBehavior' => [
                'class' => 'core\behaviors\JsonFieldsBehavior',
                'jsonAttr' => [
                    'caption',
                    'language'
                ],
            ],
        ];
    }

    /**
     * @see \core\replication\base\models\ReplicatedActiveRecordWithProps
     */
    public function getReplicationHandler()
    {
        return new InstitutionStatisticHandler();
    }

}
=========================================END====================================




=============================Пример класа обработчика===========================

<?php
namespace common\replication\mongo\handlers\organization;

use core\replication\mongo\handlers\BaseMongoReplicationHandler;

/**
 * replication handler for statistic
 * 
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
class InstitutionStatisticHandler extends BaseMongoReplicationHandler
{
    /**
     * @see \core\replication\mongo\handlers\BaseMongoReplicationHandler
     */
    public  function getReplicationModelClass()
    {
        return 'common\replication\mongo\models\organization\Institution';
    }
}

=========================================END====================================




=============================Пример модели монго===========================

<?php
namespace common\replication\mongo\models\organization;

use common\replication\mongo\models\base\BaseStatisticMongoActiveRecord;

/**
 * replication mongo model for Statistic
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
class Institution extends BaseStatisticMongoActiveRecord
{
    /**
     * @see core\replication\mongo\models\BaseRelatedMongoActiveRecord
     */
    public static function getRelatedModelClass()
    {
        return 'common\models\analytics\organization\Institution';
    }
}

=========================================END====================================