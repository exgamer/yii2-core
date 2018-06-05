<?php
namespace core\validators;

use Yii;
use yii\validators\EachValidator;
use yii\base\Exception;

/**
 * Валидатор обязательных мультиязычных полей
 * 
 * @property string $defaultLanguage - язык по умолчанию
 * @property array $requiredLanguages - массив обязательных для заполнения 
 * языков
 * @property boolean $default - признак настроек по умолчанию, если true
 * и массив обязательных языков пустой , обяхательным считается язык по умолчанию
 * @property array $languagesArray - массив языков, ключ язык, значение метка
 * 'ru' => 'Русский'
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
abstract class LanguageRequiredValidator extends EachValidator
{
    public $defaultLanguage;
    public $requiredLanguages = [];
    public $default = true;
    public $languagesArray = [];
    public $rule = [
        'string'
    ];

    public function init()
    {
        parent::init();
        $this->message = Yii::t('common','Необходимо заполнить язык по умолчанию');
        if(! $this->defaultLanguage) {
            throw new Exception(Yii::t('validators','Необходимо установить язык по умолчанию defaultLanguage.'));
        }
        if($this->default && ! $this->requiredLanguages) {
            $this->requiredLanguages = [
                $this->defaultLanguage
            ];
        }
        if(! $this->requiredLanguages) {
            throw new Exception(Yii::t('validators','Необходимо установить обязательные для заполнения языки requiredLanguages.'));
        }
    }

    public function validateAttribute($model, $attribute)
    {
        $value = $model->{$attribute};
        #избегаем повторной валидации
        if(is_string($value)) {
            return true;
        }
        parent::validateAttribute($model, $attribute);
        $errors = [];
        foreach ($this->requiredLanguages as $language) {
            $languageLabel = isset($this->languagesArray[$language]) 
                             ? "({$this->languagesArray[$language]})"
                             : null;
            if(
                ! isset($value[$language])
                || empty($value[$language])
            ) {
                $message = Yii::t('common', 'Необходимо заполнить «{label}».', [
                    'label' => "{$model->getAttributeLabel($attribute)} {$languageLabel}"
                ]);
                $errors[$attribute][] = $message;
            }
        }        
        if($errors) {
            $model->addErrors($errors);
        }
    }
}