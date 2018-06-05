<?php

namespace core\components\notice;

use yii\base\Exception;

/**
 * Модель уведомления
 * 
 * @property string $from - отправитель
 * @property string $title - заголовок
 * @property string $message - сообщение
 * @property (mixed) string | array $addresses - адресаты
 * @property array $payload - полезная нагрузка
 * @property date $send_ts - дата/время отправки уведомления
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
class NoticeMessage extends \yii\base\Model
{
    public $from;
    public $title;
    public $message;
    public $addressees;
    public $payload;
    public $send_ts;
    
    public function rules() 
    {
        return [
            [['from', 'title', 'message', 'addressees'], 'required'],
            [['from'], 'string', 'max' => 250],
            [['title'],'string','max' => 150],
            [['addressees'], 'each', 'rule' => ['string']],
            [['payload'], 'each', 'rule' => ['string']],
            [['send_ts'], 'date', 'format' => 'php:Y-m-d H:i:s'],
        ];
    }
    
    /**
     * Установка отправителя
     * 
     * @param string $f
     * @return \core\components\notice\NoticeMessage
     */
    public function setFrom(string $f) : NoticeMessage
    {
        $this->from = $f;
        
        return $this;
    }
    
    /**
     * Установка заголовка
     * 
     * @param string $t
     * @return \core\components\notice\NoticeMessage
     */
    public function setTitle(string $t) : NoticeMessage
    {
        $this->title = $t;
        
        return $this;
    }
    
    /**
     * Установка сообщения
     * 
     * @param string $m
     * @return \core\components\notice\NoticeMessage
     */
    public function setMessage(string $m) : NoticeMessage
    {
        $this->message = $m;
        
        return $this;
    }
    
    /**
     * Уставнока адресатов
     * 
     * @param array $a
     * @return \core\components\notice\NoticeMessage
     */
    public function setAddresses(array $a) : NoticeMessage
    {
        $this->addressees = $a;
        
        return $this;
    }
    
    /**
     * Уставнока полезной нагрузки
     * 
     * @param array $p
     * @return \core\components\notice\NoticeMessage
     */
    public function setPayload(array $p) : NoticeMessage
    {
        $this->payload = $p;
        
        return $this;
    }
    
    /**
     * Установка даты и времени отправки
     * 
     * @param string $s
     * @return \core\components\notice\NoticeMessage
     */
    public function setSendTs(string $s) : NoticeMessage
    {
        $this->send_ts = $s;
        
        return $this;
    }
}

/**
 * Исключение сообщения
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
class NoticeMessageException extends Exception
{
        public function getName()
        {
                return 'Notice Message Exception';
        }
}