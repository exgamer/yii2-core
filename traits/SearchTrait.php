<?php

namespace core\traits;

use Yii;
use core\data\CacheDataProvider;

/**
 * Трейт для поиска по моделям
 *
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
trait SearchTrait 
{
    /**
     * Формирование провайдера
     * 
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = self::find();
        $dataProvider = new CacheDataProvider([
            'query' => $query,
            'asArray' => $this->isArray(),
            'sort'=> [
                'defaultOrder' => [
                    'state' => SORT_DESC,
                    'id' => SORT_ASC
                ]
            ],
            'pagination' => [
                'pageSize' => $params['per-page'] ?: 50,
                'pageSizeParam' => false,
                'forcePageParam' => false
            ],
                
        ]);
        $this->load($params);
        if (!$this->validate()) {
            $query->where('0 = 1');
            
            return $dataProvider;
        }
        $this->condition($query);

        return $dataProvider;
    }
    
    /**
     * В каком виде верунть данные массив или объекты
     * @return boolean
     */
    public function isArray()
    {
        if (isset($_GET['asArray']) && $_GET['asArray']=='true'){
            return true;
        }
        
        return false;
    }
    
    /**
     * Если доступ через api отключаем ключ название формы
     * 
     * @return mixed
     */
    public function formName()
    {
        $parsers = Yii::$app->request->parsers;
        if(
                isset($parsers['application/json']) 
                && $parsers['application/json'] == 'yii\web\JsonParser'
        ){
            return '';
        }
        
        return parent::formName();
    }

    /**
     * Дополнительные условия поиска
     * 
     * @param \yii\db\ActiveQuery $query
     */
    public function condition($query){}
}
