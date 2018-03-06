<?php
namespace core\validators;

use Yii;
use yii\base\Exception;
use yii\validators\Validator;
use yii\db\ActiveRecord;
use core\helpers\StringHelper;


/**
 * Валидатор переводит в транслит выбранный атрибут, используя значение $toAttr
 * 
 * @author Kamaelkz
 */
class TranslitValidator extends Validator
{
    /**
     * Атрибут источник для транслита
     * @var string
     */
    public $source;
    
    /**
     * Менять значение при изменениии
     * @var boolean
     */
    public $changeOnEdit = false;
    public $skipOnEmpty = false;

    public function init()
    {
        parent::init();
        if (! $this->source) {
            throw new Exception(Yii::t('yii', 'Свойство {$source} должно быть установлено.'));
        }
    }
    
    public function validateAttribute($model, $attribute)
    {
        if(! $model instanceof ActiveRecord) {
            throw new Exception(Yii::t('yii', 'Модель должна быть экземпляром класса yii\db\ActiveRecord.'));
        }
        if(! $model->hasAttribute($this->source)){
            throw new Exception(Yii::t('yii', 'Объект не имеет данного свойства.'));
        }
        if($model->{$attribute} && ! $this->changeOnEdit){
            return;
        }
        $result = $model->{$this->source};
        if(is_array($result)){
            $result = reset($result);
        }
        $model->{$attribute} = StringHelper::translit($result);
    }
}