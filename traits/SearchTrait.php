<?php

namespace core\traits;

use Yii;
use core\data\CacheDataProvider;

/**
 * Трейт для поиска по моделям
 *
 * @property boolean $asArray выдавать массив или объекты
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
trait SearchTrait 
{
    public $asArray = false;
    private $_per_page = 30;
    private $_default_sort = [
        ID_FIELD => SORT_DESC
    ];
    private $_sort_attrbutes = [];

    /**
     * Общий метод получения отфильтрованных данных
     * @param array $params - параметры фильтрации
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $this->checkModel();
        $query = static::find();
        $dataProvider = new CacheDataProvider([
            'query' => $query,
            'asArray' => $this->isArray(),
            'sort'=>[
                'defaultOrder' => $this->_default_sort,
                'attributes' => $this->_sort_attrbutes,
            ],
            'pagination' => [
                'pageSize' => $this->_per_page,
                'pageSizeParam' => false,
                'forcePageParam' => false
            ],
        ]);

        $this->load($params);
        if (! $this->validate()) {
            $query->where('0 = 1');
            
            return $dataProvider;
        }
        $this->addFilters($query);

        return $dataProvider;
    }
    
    /**
     * В каком виде верунть данные массив или объекты
     * @return boolean
     */
    public function isArray()
    {
        if (isset($_GET['asArray']) && $_GET['asArray']=='true'){
            return $this->asArray = true;
        }
        
        return $this->asArray;
    }
    
    /**
     * Дополнительные условия поиска
     * @param \yii\db\ActiveQuery $query
     */
    public function addFilters(&$query)
    {
        
    } 
    
    /**
     * Проверка экзмпляра
     * @throws Exception
     */
    public function checkModel()
    {
        if(! $this instanceof IBaseSearch){
            throw new Exception(Yii::t('admin', 'Класс не является экземпляром IBaseSearch.'));
        }
    }

    /**
     * Подсчет количества выбранных фильтров
     * @return integer
     */
    public function getSelectedFilterCount()
    {
        $attrs = $this->attributes;
        $request = Yii::$app->request->get($this->formName());
        $count = 0;
        foreach ($attrs as $key => $attr) {
            if(isset($request[$key]) && $attr !== ''){
                $count++;
            } 
        }
        
        return $count;
    }
    
    /**
     * Установка кол-ва элементов на страницу
     * @param integer $v
     */
    public function setPerPage($v)
    {
        $this->_per_page = (int) $v;
    }
    
    /**
     * Установка сортировки по умолчанию
     * 
     * @param array $arr
     */
    public function setDefaultSort(array $arr)
    {
        $this->_default_sort = $arr;
    }
    
    /**
     * Установка атрибутов сортировки
     * @example :
     * ID_FIELD => [
     *       'asc' => [ID_FIELD => SORT_ASC],
     *       'desc' => [ID_FIELD => SORT_DESC],
     *       'default' => SORT_ASC,
     * ],
     * 
     * @param array $arr
     */
    public function setSortAttributes(array $arr)
    {
        $this->_sort_attrbutes;
    }
    
    /**
     * Если доступ через api отключаем ключ название формы
     * 
     * @return mixed
     */
    public function formName()
    {
        if (Yii::$app->request instanceof \yii\web\Request){
            $parsers = Yii::$app->request->parsers;
            if(
                    isset($parsers['application/json']) 
                    && $parsers['application/json'] == 'yii\web\JsonParser'
            ){
                return '';
            }
        }
        
        return parent::formName();
    }
}
