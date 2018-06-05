<?php

namespace core\traits;

use core\helpers\StringHelper;
use yii\helpers\Json;

trait ModelTrait
{
    /**
     * Возвращает ошиибку сервера
     * 
     * @param string|Exception $m
     */
    public function addServerError($m)
    {
        if($m instanceof \Exception){
            $m = $m->getMessage();
        }
        $this->parseError($m);
    }
    
    /**
     * Разбирает ошибку от PDS и приводит ее к общему виду
     * Иначе устанавливает текущую ошибку
     * 
     * @param string $message
     * 
     * @return array|null
     */
    protected function parseError($message)
    {
        if(! StringHelper::isJson($message)) {
            $this->addError('server-error' , $message);
            
            return;
        }
        
        $json = Json::decode($message);
        if(! is_array($json)) {
            $this->addError('server-error' , $message);
            
            return;
        }
        
        foreach ($json as $error) {
            if(! isset($error['field']) || ! isset($error['message'])) {
                continue;
            }
            $this->addError($error['field'] , $error['message']);
        }
    }
}