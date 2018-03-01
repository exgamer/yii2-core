<?php
namespace core\traits;

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
        $this->addError('server-error' , $m);
    }
}

