<?php
namespace core\services;

use yii\base\Exception;
use yii\base\Component;

/**
 * Базовый Service для моделей
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class AService extends Component
{          
    public $errors=[];
    
    /**
     * Получить ошибки
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}

class AServiceException extends Exception 
{
        public function getName()
        {
                return 'ABaseService exception';
        }
}