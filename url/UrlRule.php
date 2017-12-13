<?php

namespace core\url;

class UrlRule extends \yii\rest\UrlRule
{

    public $tokens = [
        '{id}' => '<id:.*>',
    ];

    public $patterns = [
        'POST set-status/{id}' => 'set-status',
        'PUT,PATCH {id}' => 'update',
        'POST batch-delete'=>'batch-delete',
        'DELETE {id}' => 'delete',
        'DELETE' => 'delete',
        'GET info' => 'info',
        'GET count' => 'count',
        'GET,HEAD {id}' => 'view',
        'POST batch'=>'batch-insert',
        'POST' => 'create',
        'GET,HEAD' => 'index',
        '{id}' => 'options',
        '' => 'options',
    ];

}