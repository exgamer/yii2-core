<?php
namespace core\validators;

use Yii;
use yii\base\Exception;
use yii\validators\Validator;
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
        if(! $model->hasAttribute($this->source)){
            throw new Exception(Yii::t('yii', 'Объект не имеет данного свойства.'));
        }
        if($model->{$attribute} && ! $this->changeOnEdit){
            return;
        }
        $string = $model->{$this->source};
        if(is_array($string)){
            $string = reset($string);
        }
        $model->{$attribute} = StringHelper::translit($string);
    }
}