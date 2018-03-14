<?php
namespace core\validators;

use Yii;
use yii\validators\Validator;

/**
 * Универсальный валидатор для возможности валидации массивов данных с помощью моделей
 * @author CitizenZet
 */
class ModelValidator extends Validator
{
    public $modelClass;
    
    public function init()
    {
        parent::init();
        if ($this->modelClass === null) {
            $this->modelClass = Yii::t('yii', '{attribute} must set a model class.');
        }
    }
    
    public function validateAttribute($model, $attribute)
    {
        $model = new $this->modelClass();
        $model->load($model->{$attribute}, '');
        if (! $model->validate()){
            $this->addError($model, $attribute,  Json::encode($model->getErrors()));
            return false;
        }
        $this->{$attribute} = $model->attributes;
        
        return true;
    }
    
    protected function validateValue($value)
    {
        $model = new $this->modelClass();
        $model->load($value, '');
        if (! $model->validate()){
            return [Yii::t('api', Json::encode($model->getErrors())), []];
        }
        $this->{$attribute} = $model->attributes;
        
        return true;
    }
}