<?php
namespace core\cache;

class FileCache extends \yii\caching\FileCache
{
    protected $is_caching = true;
    public $servers = [];
    public $useMemcached = false;
    
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