<?php

namespace core\components\notice;

use Yii;
use yii\helpers\Json;
use yii\base\Exception;
use core\components\notice\NoticeMessage;
use core\components\notice\NoticeMessageException;
use core\components\notice\EmailHandler;
use core\components\notice\PusheHandler;

/**
 * Класс агрегатор отправки увемдолений
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
class NoticeSender extends \yii\base\Component
{
    const PUSH = 1;
    const EMAIL = 2;
    const SMS = 3; #не реализовано
    
    /**
     * Ключ доступа к API нотификатора
     * @var string
     */
    public $apiKey;

    /**
     * Установка ключа доступа к API
     * 
     * @param string $value
     */
    public function setApiKey($value)
    {
        $this->apiKey = $value;
    }

    /**
     * Отправка письма
     * 
     * @param NoticeMessage $message
     * @return boolean
     */
    public function sendEmail(NoticeMessage $message)
    {
        return $this->send(self::EMAIL, $message);
    }
    
    /**
     * Отправка push уведомления
     * 
     * @param NoticeMessage $message
     * @return boolean
     */
    public function sendPush(NoticeMessage $message)
    {
        return $this->send(self::PUSH, $message);
    }

    /**
     * Отправка уведомления
     * 
     * @param integer $type
     * @param NoticeMessage $message
     * 
     * @return boolean
     */
    private function send($type, NoticeMessage $message)
    {
        if(! $message->validate()) {
            $errors = Json::encode($message->getErrors());
            throw new NoticeMessageException($errors);
        }
        $handler = $this->getHandlerInstance($type);
        if(! $handler->send($message)) {
            throw new  NoticeSenderException(Yii::t('common', 'Ошибка отправки сообщения.'));
        }
        
        return true;
    }
    
    /**
     * Получение экземпляра обработчика увемдоления
     * 
     * @param integer $type
     * @throws NoticeSenderException
     */
    private function getHandlerInstance($type)
    {
        $class = null;
        switch ($type) {
            case self::PUSH :
                $class = PusheHandler::class;
                break;
            case self::EMAIL :
                $class = EmailHandler::class;
                break;
            default :
                throw new NoticeSenderException(Yii::t('common', 'Не определен тип отправки уведомления.'));
        }
        #прверка устновленного ключа
        if(! $this->apiKey) {
            throw new  NoticeSenderException(Yii::t('common', 'Не передан ключ доступа к API.'));
        }
        
        return ( new $class([], $this->apiKey) );
    }
}

/**
 * Исключение компонента
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
class NoticeSenderException extends Exception
{
        public function getName()
        {
                return 'Notice Sender Exception';
        }
}

