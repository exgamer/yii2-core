<?php
namespace core\traits;

use Yii;
use yii\base\Exception;
use yii\db\ActiveQuery;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use core\interfaces\IBaseSearch;
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
    /**
     * Класс дата провайдера
     * 
     * @var string
     */
    public $dataProviderClass = '\yii\data\ActiveDataProvider';
    
    /**
     * Признак вывода данных как массив
     * 
     * @var boolean
     */
    public $asArray = false;
    
    /**
     * Сортировка по умолчанию DataProvider
     * 
     * @var array
     */
    private $_default_sort = null;
    
    /**
     * Атрибуты для сортировки DataProvider
     * 
     * @var array
     */
    private $_sort_attrbutes = null;
    
    /**
     * Кол-во элементов на страницу DataProvider
     * 
     * @var integer 
     */
    private $_per_page = 30;
    
    /**
     * Состояние пагинации
     * 
     * @var boolean
     */
    private $_pagination_state = true;
    
    /**
     * Состояние отображения ошибок валидации
     * 
     * @var boolean
     */
    private $_validation_error_state = true;

    /**
     * Массив параметров переданных в параметры запроса expand
     * 
     * @var array 
     */
    private $_expandParams = [];

    /**
     * Поиск одной записи
     * 
     * @param ActiveQuery $query
     * @param @param array $params - параметры фильтрации
     * @return ActiveRecord || null
     */
    public function searchOne($query, $params)
    {
        $this->scenario = ActiveRecord::SCENARIO_SEARCH;
        $this->load($params);
        if (! $this->validate()) {
            return $this->getErrors();
        }
        // Применяем фильтр к запросу
        $this->addFilters($query);
        $this->eagerLoading($query);
        
        return $query->one();
    }
    
    /**
     * Общий метод получения отфильтрованных данных
     * @param array $params - параметры фильтрации
     *
     * @return ActiveDataProvider
     */
    public function search($params, $with = [])
    {
        $this->checkModel();
        $query = static::find();
        $dpConfig = [
            'query' => $query,
            'sort'=>[
                'attributes' => $this->getSortAttributes(),
                'defaultOrder' => $this->getDefaultSort(),
            ],
            'pagination' => $this->getPagination()
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
            if(! $this->_validation_error_state) {
                $query->where('0 = 1');
            } else {
                return $this;
            }
        }
        
        $this->addFilters($query);
        if(! $with) {
            $this->eagerLoading($query);
        } else {
            $query->with = $with;
        }

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
            if(! $this->isExpandField($filed) || ! $this->checkRelation($filed)) {
                continue;
            }
            $query->with($filed);
        }
    }
    
    /**
     * Проверка на существования релейшина
     * 
     * @param string $expand
     * @return boolean
     */
    private function checkRelation($expand)
    {
        $method = "get{$expand}";
        #существование метода
        if(! method_exists($this, $method)){
            return false;
        }
        $result = $this->{$method}();
        #экземпляр ActiveQuery
        if(! $result instanceof ActiveQuery){
            return false;
        }
        
        return true;
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
     * Установка состояния отображения ошибок валидации
     * 
     * @param boolean $state
     */
    public function setValidationErrorState($state)
    {
        $this->_validation_error_state = (boolean) $state;
    }

    /**
     * Установка состояниея пагинации
     * 
     * @param boolean $state
     */
    public function setPaginationState($state)
    {
        $this->_pagination_state = (boolean) $state;
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
     * Получение настроек пагинации
     * 
     * @return boolean|array
     */
    private function getPagination()
    {
        if(! $this->_pagination_state) {
            return false;
        } 
        return [
            'pageSize' => $this->getPerPage(),
            'pageSizeParam' => false,
            'forcePageParam' => false
        ];
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
