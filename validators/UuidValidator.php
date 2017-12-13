<?php
namespace core\validators;

use Yii;
use yii\validators\StringValidator;

/**
 * Валидатор для uuid
 * проверяет строку на длину 36 мимволов и проверяет на соответсвие шаблону
 * 
 * @property string $pattern  - решулярное выражение (шаблон)
 * 
 * @override yii\validators\StringValidator
 */
class UuidValidator extends StringValidator
{
    const LAST_STACK = "00003b9ac9ff";
    
    const LENGTH = 36;
    
    public $not = false;
    
    public $pattern = '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-' . self::LAST_STACK . '$/i';
    
    public function init()
    {
        parent::init();
        if ($this->pattern === null) {
            throw new InvalidConfigException('The "pattern" property must be set.');
        }
        $this->message = Yii::t('api','Неверное значение uuid');
    }

    public function validateAttribute($model, $attribute)
    {
        #валидация строки и длины
        $value = $model->{$attribute};
        if (!is_string($value)) {
            $this->addError($model, $attribute, $this->message);

            return;
        }
        $length = mb_strlen($value, $this->encoding);
        if ($length > self::LENGTH || $length < self::LENGTH) {
            $this->addError($model, $attribute, $this->message);
        }
        #валидация по регульрному вырожению
        $validPattern = ! is_array($value) &&
            (!$this->not && preg_match($this->pattern, $value)
            || $this->not && !preg_match($this->pattern, $value));
        
        if(! $validPattern){
            $this->addError($model, $attribute, $this->message);
        }
    }
    
    public function validateValue($value) 
    {
        if (!is_string($value)) {
            return [$this->message, []];
        }
        $length = mb_strlen($value, $this->encoding);
        if ($length > self::LENGTH || $length < self::LENGTH) {
            return [$this->message, []];
        }
        $validPattern = ! is_array($value) &&
            (!$this->not && preg_match($this->pattern, $value)
            || $this->not && !preg_match($this->pattern, $value));
        
        if(! $validPattern){
            return [$this->message, []];
        }
    }

}