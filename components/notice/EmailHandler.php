<?php

namespace core\components\notice;

use core\components\notice\base\ANoticeHandler;

/**
 * Обработчик отправки электронного письма
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
class EmailHandler extends ANoticeHandler
{
    protected $url = 'https://api.bilimal.kz/notice/email';
}

