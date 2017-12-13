<?php
namespace core\validators;

use yii\validators\Validator;
use yii\validators\NumberValidator;

/**
 * Валидатор для проверки атрибутов которые могут быть просто числами и массивами чисел и еще принимать строку с числами через запятую
 * @author CitizenZet
 */
class MixedIntegerValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        $attrValue = $model->{$attribute};
        $validator = new NumberValidator();
        $validator->integerOnly=true;
        if(is_string($attrValue)){
            $result = null;
            $array= explode(',', $attrValue);
            foreach ($array as $value) {
                if (! $validator->validate($value)){
                    return [Yii::t('api', "Значение должно быть целочисленным = ".$val),[]];
                }
                
                $result[] = (int)$value;
            }
            
            return true;
        }
        
        if (is_array($attrValue)){
            foreach ($attrValue as $val) {
                if (! $validator->validate($val)){
                    return [Yii::t('api', "Значение должно быть целочисленным = ".$val),[]];
                }
            }
            
            return true;
        }

        if (! $validator->validate($attrValue)){
            return [Yii::t('api', "Значение должно быть целочисленным = ".$val),[]];
        }

        return null;
    }
    
    protected function validateValue($value)
    {
        $attrValue = $value;
        $validator = new NumberValidator();
        $validator->integerOnly=true;
        if(is_string($attrValue)){
            $result = null;
            $array= explode(',', $attrValue);
            foreach ($array as $value) {
                if (! $validator->validate($value)){
                    return [Yii::t('api', "Значение должно быть целочисленным = ".$val),[]];
                }
                
                $result[] = (int)$value;
            }
            
            return true;
        }
        
        if (is_array($attrValue)){
            foreach ($attrValue as $val) {
                if (! $validator->validate($val)){
                    return [Yii::t('api', "Значение должно быть целочисленным = ".$val),[]];
                }
            }
            
            return true;
        }

        if (! $validator->validate($attrValue)){
            return [Yii::t('api', "Значение должно быть целочисленным = ".$val),[]];
        }

        return null;
    }
}