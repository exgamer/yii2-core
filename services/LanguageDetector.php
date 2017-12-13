<?php
namespace core\services;

use yii\base\Component;

/**
 * Определяет переданный в заголовках язык и устанавливает его в качестве используемеого языка.
 * Class LanguageDetector
 * @package app\components
 */
class LanguageDetector extends Component
{

    public $languages = [
        'ru-RU' => 1,
        'kk-KZ' => 2,
        'en-US' => 3
    ];

    public function getLang()
    {
        return \Yii::$app->request->getHeaders()->get("X-LANG") ? \Yii::$app->request->getHeaders()->get("X-LANG") : \Yii::$app->language;
    }

    /**
     * приводим язык в формат ISO 
     * пример: ru-RU -> ru
     * @return string
     */
    public function getIso()
    {
        return substr($this->getLang(), 0,2);
    }
    
    public function getLangId($lang = null)
    {

        if (!$lang) {
            $lang = $this->lang;
        }

        \Yii::trace("LANGUAGE IS ".$lang);

        if (isset($this->languages[$lang])) {
            return $this->languages[$lang];
        }
        return 1;
    }

}