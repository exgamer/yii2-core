<?php

namespace core\components\notice;

use core\components\notice\base\ANoticeHandler;

/**
 * Обработчик отправки push уведмоления
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
class PushHandler extends ANoticeHandler
{
    protected $url = 'https://api.bilimal.kz/notice/push';
}