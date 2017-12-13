<?php

namespace core\cache;

use yii\base\ErrorException;

class MemCache extends \yii\caching\MemCache
{
    public function get($key)
    {
        \Yii::trace("GET FROM CACHE ".print_r($key,true));
        if (\Yii::$app->request->getHeaders()->get("Cache-Control") == "no-cache" OR \Yii::$app->request->getHeaders()->get("Pragma") == "no-cache" OR !$this->getIsCaching())
        {
            \Yii::trace("RESET CACHE");
            \Yii::$app->cache->delete($key);
            return false;
        }
        $start = microtime(true);
        try {
            $data = parent::get($key);
        } catch (ErrorException $e) {
            \Yii::$app->cache->delete($key);
            \Yii::trace("MEMCACHE TO BIG ". $key);
            return false;
        }
        $time = microtime(true) - $start;
        \Yii::trace("LOADED FROM CACHE in $time second.");
        return $data;
    }

    protected $is_caching = true;
    public function getIsCaching()
    {
        return $this->is_caching;
    }

    public function pause()
    {
        $this->is_caching = false;
    }

    public function resume()
    {
        $this->is_caching = true;
    }
}

?>