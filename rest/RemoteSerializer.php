<?php
namespace core\rest;

use Yii;
use core\rest\Serializer;
use yii\base\Exception;
use core\remote\ACommunicator;
use core\traits\CommunicatorTrait;

/**
 * Базовый класс для обработки данных во время сериализации
 * 
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
class RemoteSerializer extends Serializer
{
    use CommunicatorTrait;
    
    /**
     * Действия до сериализации локальных данных
     * (ссылка потому что может прилететь массив)
     * @param mixed $data
     */
    protected function beforeLocalDataSerialize(&$data)
    {
        
    }
    
    /**
     * Действия после сериализации локальных данных
     * @param array $data - локальные данные
     * @param array $remoteData - удаленные даннные
     */
    protected function afterLocalDataSerialize(&$data, $remoteData)
    {

    }
    
    public function serialize($data)
    {
        $start = microtime(true);
        $this->beforeLocalDataSerialize($data);
        $data = parent::serialize($data);
        $communicator = $this->getCommunicator();
        if (! $communicator instanceof ACommunicator){
            throw new Exception(Yii::t('api', 'Комуникатор должен быть комуникатором!! Вот так вот!'));
        }
        $remoteData = $communicator->sendRequest();
        $this->afterLocalDataSerialize($data, $remoteData);
        if (\Yii::$app->controller->deep_cache AND \Yii::$app->controller->action->id == "index") {
            $cache_string = "deep_".md5(json_encode($this->request->queryParams).\Yii::$app->user->id);
            if (!\Yii::$app->cache->exists($cache_string)) {
                \Yii::$app->cache->set($cache_string, $data, 3600);
                \Yii::trace("PLACED DATA IN DEEP CACHE");
            }
        }

        $time = microtime(true) - $start;
        \Yii::trace("SERIALIZED in $time second.");
        return $data;
    }

}