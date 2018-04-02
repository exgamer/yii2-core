<?php
namespace core\validators;

use yii\validators\Validator;

class JsonValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        if (is_array($model->$attribute)){
            $model->$attribute = json_encode($model->$attribute);
        }
        if (!json_decode($model->$attribute)){
            $this->addError($model, $attribute, 'Not valid json object');
            return false;
        }
//        if (!is_array($model->$attribute)) {
//            $this->addError($model, $attribute, 'Must be array to serialize');
//            return false;
//        }
//
//        $json = json_encode($model->$attribute);
//        if (!$json) {
//            $this->addError($model, $attribute, 'Not valid json object');
//            return false;
//        }

        //$model->$attribute = $json;
        return true;

    }
}