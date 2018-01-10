<?php
namespace core\services;

use Yii;
use yii\base\Exception;
use yii\base\Component;
use yii\web\ServerErrorHttpException;

/**
 * Базовый Service для моделей
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class AService extends Component
{          
    public $errors=[];
    
    /**
     * @return \yii\db\Connection
     */
    public static function getDb()
    {
        if (Yii::$app->has('db')){
            return Yii::$app->db;
        }
        
        throw new ServerErrorHttpException(
                Yii::t('api', 'Не определена БД для работы сервиса.')
        );
    }
    
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