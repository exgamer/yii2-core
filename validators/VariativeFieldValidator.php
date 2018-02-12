<?php
namespace core\validators;

use yii\validators\Validator;

/**
 * Универсальный валидатор для возможности валидации типов данных в массиве или строке через запятую
 * @author CitizenZet
 */
class VariativeFieldValidator extends Validator
{
    public $validatorClass;
    public $integerOnly = false;
    
    public function init()
    {
        parent::init();
        if ($this->validatorClass === null) {
            Yii::t('yii', '{attribute} must set a validator class.');
        }
        if ($this->integerOnly) {
            $this->integerOnly = true;
        }
    }
    
    public function validateAttribute($model, $attribute)
    {
        $validator = new $this->validatorClass();
        if ($this->integerOnly){
            $validator->integerOnly=true;
        }
        if (is_array($this->{$attribute})){
            foreach ($this->{$attribute} as $val) {
                if (! $validator->validate($val)){
                    $this->addError($attribute,Yii::t('api', "Неверное значение = ".$val));
                    return false;
                }
            }
            
            return true;
        }
        
        if(stristr($this->{$attribute}, ',')){
            $result = null;
            $array= explode(',', $this->{$attribute});
            foreach ($array as $value) {
                if (! $validator->validate($value)){
                    $this->addError($attribute,Yii::t('api', "Неверное значение = ".$val));
                    return false;
                }
                
                $result[] = $value;
            }
            $this->{$attribute} = $result;
            
            return true;
        }
        


        if (! $validator->validate($this->{$attribute})){
            $this->addError($attribute,Yii::t('api', "Неверное значение = ".$this->{$attribute}));
            return false;
        }

        return true;
    }
    
    protected function validateValue($value)
    {
        $validator = new $this->validatorClass();
        if ($this->integerOnly){
            $validator->integerOnly=true;
        }
        if (is_array($value)){
            foreach ($value as $val) {
                if (! $validator->validate($val)){
                    $this->addError($attribute,Yii::t('api', "Неверное значение = ".$val));
                    return false;
                }
            }
            
            return true;
        }
        
        if(stristr($value, ',')){
            $result = null;
            $array= explode(',', $value);
            foreach ($array as $val) {
                if (! $validator->validate($val)){
                    $this->addError($attribute,Yii::t('api', "Неверное значение = ".$val));
                    return false;
                }
                
                $result[] = $val;
            }
            $value = $result;
            
            return true;
        }
        


        if (! $validator->validate($value)){
            $this->addError($attribute,Yii::t('api', "Неверное значение = ".$value));
            return false;
        }

        return true;
    }
}