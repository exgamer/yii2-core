<?php
namespace core\remote\queries;

use core\queries\BaseActiveQuery;
use core\traits\RemoteBaseActiveQueryTrait;
use core\traits\MixedRemoteBaseActiveQueryAdditionalTrait;

/**
 * Базовый ActiveQuery для комбинированных запросов
 * TODO допилить полную поддержку ActiveQuery
 * работает только one и all
 * так можно только указывать where
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
class MixedRemoteBaseActiveQuery extends BaseActiveQuery
{
    use RemoteBaseActiveQueryTrait;
    use MixedRemoteBaseActiveQueryAdditionalTrait;
   
}