<?php
namespace core\rest;

class Serializer extends \yii\rest\Serializer
{

    public function serialize($data)
    {
        $start = microtime(true);
        $data = parent::serialize($data);

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