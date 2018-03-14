<?php
namespace core\validators;

use Yii;
use yii\validators\Validator;
use yii\helpers\Json;

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
        $validatorModel = new $this->modelClass();
        $validatorModel->load($model->{$attribute}, '');
        if (! $validatorModel->validate()){
            $this->addError($model, $attribute,  Json::encode($validatorModel->getErrors()));
            return false;
        }
        $model->{$attribute} = $validatorModel->attributes;
        
        return true;
    }
    
    protected function validateValue($value)
    {
        $model = new $this->modelClass();
        $model->load($value, '');
        if (! $model->validate()){
            return [Yii::t('api', Json::encode($model->getErrors())), []];
        }
        $value = $model->attributes;
        
        return true;
    }
}