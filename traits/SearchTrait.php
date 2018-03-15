<?php

namespace core\traits;

use Yii;
use core\data\CacheDataProvider;
use yii\data\ActiveDataProvider;
use yii\base\Exception;
use core\interfaces\IBaseSearch;

/**
 * Трейт для поиска по моделям
 *
 * @property boolean $asArray выдавать массив или объекты
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
trait SearchTrait 
{
    
    private $_asArray = false;
    private $_per_page = 30;
    private $_default_sort = null;
    private $_sort_attrbutes = null;

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
        if ($this->isCashe()){
            $dataProvider = new CacheDataProvider([
                'query' => $query,
                'asArray' => $this->isArray(),
                'sort'=>[
                    'attributes' => $this->getSortAttributes(),
                    'defaultOrder' => $this->getDefaultSort(),
                ],
                'pagination' => [
                    'pageSize' => $this->getPerPage(),
                    'pageSizeParam' => false,
                    'forcePageParam' => false
                ],
            ]);
        }else{
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'sort'=>[
                    'attributes' => $this->getSortAttributes(),
                    'defaultOrder' => $this->getDefaultSort(),
                ],
                'pagination' => [
                    'pageSize' => $this->getPerPage(),
                    'pageSizeParam' => false,
                    'forcePageParam' => false
                ],
            ]);
        }

        $this->load($params);
        if (! $this->validate()) {
            $query->where('0 = 1');
            
            return $dataProvider;
        }
        $this->addFilters($query);

        return $dataProvider;
    }
    
    /**
     * Использовать ли cashedataprovider
     * @return boolean
     */
    public function isCashe()
    {
        return true;
    }
    
    /**
     * В каком виде верунть данные массив или объекты
     * @return boolean
     */
    public function isArray()
    {
        return (boolean) Yii::$app->request->get('asArray', false);
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
     * Получение кол-во элементов на страницу
     * 
     * @return integer
     */
    public function getPerPage()
    {
        return (int) Yii::$app->request->get('per-page', $this->_per_page);
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
     * Получение сортировки по умолчанию
     * 
     * @return array
     */
    private function getDefaultSort()
    {
        if(! $this->hasAttribute('id') || $this->_default_sort) {
            return $this->_default_sort;
        }
        if(! isset($this->_sort_attrbutes['id']) && (array_search('id', $this->_sort_attrbutes) === false)) {
            return;
        }
        
        return ['id' => SORT_DESC];
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
     * @param array $sort
     */
    public function setSortAttributes(array $sort)
    {
        $this->_sort_attrbutes = $sort;
    }
    
    /**
     * Возвращает атрибуты для сортировки
     * 
     * @return array
     */
    public function getSortAttributes()
    {
        if(! $this->_sort_attrbutes) {
            $this->_sort_attrbutes = array_keys($this->attributes);
        }
        
        return $this->_sort_attrbutes;
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