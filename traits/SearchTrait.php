<?php
namespace core\traits;

use Yii;
use yii\base\Exception;
use core\interfaces\IBaseSearch;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use core\models\ActiveRecord;

/**
 * Трейт для поиска по моделям
 *
 * @property boolean $asArray выдавать массив или объекты
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
trait SearchTrait 
{
    public $dataProviderClass = '\yii\data\ActiveDataProvider';
    public $asArray = false;
    private $_per_page = 30;
    private $_default_sort = null;
    private $_sort_attrbutes = null;
    
    /**
     * Массив параметров переданных в параметры запроса expand
     * @var array 
     */
    private $_expandParams = [];

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
        $dpConfig = [
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
        ];
//        if ($this->isCashe()){
//            $this->dataProviderClass = '\core\data\CacheDataProvider';
//            $dpConfig['asArray'] = $this->isArray();
//        }
        $dataProviderClass = $this->getDataProviderClass();
        $dataProvider = new $dataProviderClass($dpConfig);
        $this->scenario = ActiveRecord::SCENARIO_SEARCH;
        $this->load($params);
        if (! $this->validate()) {
            $query->where('0 = 1');
            
            return $dataProvider;
        }
        
        $this->addFilters($query);
        $this->eagerLoading($query);

        return $dataProvider;
    }
    
    /**
     * Получить класс дата провеидера
     * @return string
     */
    public function getDataProviderClass()
    {
        return '\yii\data\ActiveDataProvider';
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
        if (Yii::$app->request instanceof \yii\web\Request){
            return (boolean) Yii::$app->request->get('asArray', false);
        }
        
        return $this->asArray;
    }
    
    /**
     * Дополнительные условия поиска
     * 
     * @param \yii\db\ActiveQuery $query
     */
    public function addFilters(&$query)
    {
        
    } 
    
    /**
     * Жадная загрузка экспандов
     * 
     * @param ActiveQuery $query
     */
    private function eagerLoading(&$query)
    {
        $this->setExpandParams();
        $extraFields = $this->extraFields();
        if(! $extraFields) {
            return;
        }
        foreach($extraFields as $filed) {
            if(! $this->isExpandField($filed)) {
                continue;
            }
            $query->with($filed);
        }
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
        $request = Yii::$app->request->get($this->formName());
        $result = [];
        foreach ($this->getValidators() as $validator) {
            $attrs = $validator->attributes;
            foreach ($attrs as $attr) {
                if(! isset($request[$attr]) || $request[$attr] == null) {
                    continue;
                }
                $result[$attr] = true;
            }  
        }
                
        return count($result);
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
        if (Yii::$app->request instanceof \yii\web\Request){
            return (int) Yii::$app->request->get('per-page', $this->_per_page);
        }
        
        return $this->_per_page;
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
    
    /**
     * Переопредено для возможности использования searchв монго 
     * @see yii\mongodb\ActiveRecord
     */
    public static function collectionName()
    {
        return Inflector::camel2id(StringHelper::basename(get_parent_class()), '_');
    }
    
    /**
     * Устанавливает массив элементов из параметра expand запроса
     */
    private function setExpandParams()
    {
        if($this->_expandParams){
            return null;
        }
        $params = explode(",", Yii::$app->request->get("expand"));
        $values = array_values($params);
        $this->_expandParams = array_combine($values, $values);
    }
    
    /**
     * Проверяет передан ли атрибут в параметр expand
     * 
     * @param string $field
     * @return boolean
     */
    public function isExpandField($field)
    {
        $this->setExpandParams();
        if(! isset($this->_expandParams[$field])){
            return false;
        }
        
        return true;
    }
}