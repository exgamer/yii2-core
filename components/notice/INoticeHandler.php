<?php

namespace core\components\notice;

use core\components\notice\NoticeMessage;

/**
 * Интерфейс обработчиков уведомлений
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
interface INoticeHandler
{
    /**
     * Отправка сообщения
     * 
     * @param NoticeMessage $message
     * @return boolean
     */
    public function send(NoticeMessage $message) : bool;
}