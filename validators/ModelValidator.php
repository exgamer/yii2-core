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
    public $asArray = false;
    public $errors = [];
    
    public function init()
    {
        parent::init();
        if ($this->modelClass === null) {
            $this->modelClass = Yii::t('yii', '{attribute} must set a model class.');
        }
        if ($this->asArray) {
            $this->asArray = true;
        }
    }
    
    public function validateAttribute($model, $attribute)
    {
        if (! $this->asArray){
            $result = $this->validateModel($model->{$attribute});
            if ($result == false){
                $this->addError($model, $attribute,  Json::encode($this->errors));
                return false;
            } 
            $model->{$attribute} = $result;
            
            return true;
        }
        $dataArray = [];
        foreach ($model->{$attribute} as $data) {
            $result = $this->validateModel($data);
            if ($result == false){
                $this->addError($model, $attribute,  Json::encode($this->errors));
                return false;
            } 
            $dataArray[] = $result;
        }
        if (! empty($dataArray)){
            $model->{$attribute} = $dataArray;
        }
        
        return true;
    }
//    
//    protected function validateValue($value)
//    {
//        
//        $model = new $this->modelClass();
//        $model->load($value, '');
//        if (! $model->validate()){
//            return [Yii::t('api', Json::encode($model->getErrors())), []];
//        }
//        $value = $model->attributes;
//        
//        return true;
//    }
    
    protected function validateModel($value)
    {
        $validatorModel = new $this->modelClass();
        $validatorModel->load($value, '');
        if (! $validatorModel->validate()){
            $this->errors[] = $validatorModel->getErrors();

            return false;
        }
        
        return $validatorModel->attributes;
    }
}