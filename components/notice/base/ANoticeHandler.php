<?php

namespace core\components\notice\base;

use core\remote\ABaseCommunicator;
use core\components\notice\INoticeHandler;
use core\components\notice\NoticeMessage;

/**
 * Базовый обработчик уведомлений
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
abstract class ANoticeHandler extends ABaseCommunicator implements INoticeHandler
{
    protected $method = 'POST'; 
    protected $success_http_codes = [
        201
    ];  
    protected $headers = [
        'Content-Type: application/json',
    ];
    
    public function __construct($config = [], $apiKey) 
    {
        parent::__construct($config);
        $this->addHeaders(["Access-Token: {$apiKey}"]);
    }

    /**
     * @see \core\components\notice\INoticeHandler
     * @param \core\components\notice\NoticeMessage $message
     */
    public function send(NoticeMessage $message) : bool
    {
        $this->setPostfields($message->attributes);
        $data = $this->sendRequest();
        if(! $data) {
            return false;
        }
        
        return true;
    }
}

