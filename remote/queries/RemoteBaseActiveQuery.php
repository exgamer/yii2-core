<?php
namespace core\remote\queries;

use core\queries\BaseActiveQuery;
use core\traits\RemoteBaseActiveQueryTrait;
use core\traits\RemoteBaseActiveQueryAdditionalTrait;

/**
 * Базовый ActiveQuery для запросов на удаленный ресурс
 * TODO допилить полную поддержку ActiveQuery
 * работает только one и all
 * так можно только указывать where
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
class RemoteBaseActiveQuery extends BaseActiveQuery
{
    use RemoteBaseActiveQueryTrait;
    use RemoteBaseActiveQueryAdditionalTrait;
}
