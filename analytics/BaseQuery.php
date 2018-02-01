<?php
namespace core\analytics;

use Yii;
use yii\base\Exception;

/**
 * Base class for long term and non dynamic analytics collect
 * 
 * @property integer $pageSize - on page items count
 * @property integer $lastDataCount - last sql result rows count
 * @property integer $totalCount - total data count
 * @property integer $currentPage - current page
 * @property integer $targetPage - target page
 * @property boolean $bySinglePage - procees by single page
 * @property date    $dateFrom    - начало периода сбора инфы
 * @property date    $dateTo      - конец периода сбора инфы
 * @property array   $subQueries  - массив запросов которы нужно выполнить в ходе обработки
 * @property string  $queriesNameSpacePath  - namespace место где лежат query
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class BaseQuery
{
    public $origin_db;
    public $pageSize = 50;
    public $lastDataCount = 0;
    public $totalCount = 0;
    public $currentPage = 0;
    public $targetPage = 0;
    public $bySinglePage = false;
    public $dateFrom;
    public $dateTo;
    public $subQueries = [];
    public $queriesNameSpacePath = 'console\modules\analytics\queries';

    function  __construct($dateFrom = null, $dateTo = null, $subQueries = null, &$inputData = null)
    {
        $this->setPeriod($dateFrom, $dateTo);
        if ($subQueries && is_array($subQueries)){
            $this->subQueries = $subQueries;
        }
    }
    
    /**
     * установка периода сбора
     * @param date $dateFrom
     * @param date $dateTo
     */
    public function setPeriod($dateFrom, $dateTo)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        if (! $this->dateTo){
            $this->dateTo = date('Y-m-d');
        }
        if (! $this->dateFrom){
            $this->setDateFrom();
        }
    }
    
    /**
     * Правила установка даты с, если она не указана
     * по умолчанию выставляем дату с на начало учебного года 
     * если надо по другому переопредели
     */
    public function setDateFrom()
    {
        $current_year = self::getCurrentAcademicYear($this->dateTo);
        $this->dateFrom = $current_year."-09-01";
    }
    
    /**
     * Получить начало текущего академ года по дате
     */
    public static function getCurrentAcademicYear($date)
    {
        $current_month = (int)date('m', strtotime($date));
        $current_year  = (int)date('Y', strtotime($date));
        if ($current_month > 0 AND $current_month < 8){
            $current_year--;
        }
        
        return $current_year;
    }
    
    /**
     * выполняет запрос и обработку данных
     * 
     * @param array $inputData
     * @return type
     */
    public function execute(&$inputData = null)
    {
        $this->beforeExecute($inputData);
        if (! $this->isExecute($inputData))
        {
            return true;
        }
        do {
            $this->_execute($inputData);
        } while (! $this->isDone());
        
        return true;
    }
    
    /**
     * exec query
     * 
     * @param array $inputData //массив с входными данными, например для внесения полученных данных
     * 
     * @return boolean
     */
    public function _execute(&$inputData = null)
    {
        $dataArray = $this->executeSql();
        $this->lastDataCount = count($dataArray);
        if ($this->lastDataCount == 0){

            return true;
        }
        foreach ($dataArray as $data) {
            try{
                $this->prepareData($data);
                $this->executeSubQueries($data);
                $this->processData($data, $inputData);
                $this->finishProcess($data, $inputData);
            } catch (\yii\db\Exception $dbEx){
                $this->noDbConnectionExceptionActions($data, $dbEx);
                continue;
            }
        }
        $this->afterPageProcess($inputData);
        $this->currentPage++;
        if ($this->bySinglePage){
            $this->lastDataCount = 0;
        }
        
        return true;
    }

    public function executeSubQueries(&$inputData)
    {
        if (! $this->subQueries || !is_array($this->subQueries)){
            return;
        }
        foreach ($this->subQueries as $queryClass) {
            $class = $this->queriesNameSpacePath.'\\'.$queryClass;
            $query = new $class($this->dateFrom, $this->dateTo, null, $inputData);
            $query->execute($inputData);
        }
    }
    
    /**
     * get db connection
     * 
     * @return Connection
     * @throws Exception
     */
    public function getOriginDb()
    {
        if (! $this->origin_db){
            throw new Exception(Yii::t('api','Db connection is not setted.'), 500);
        }
        
        return $this->origin_db;
    }
    
    /**
     * set db connection
     * @param Connection $db
     */
    public function setOriginDb($origin_db)
    {
        $this->origin_db = $origin_db;
    }
    
    /**
     * get lastDataCount
     * 
     * @return integer
     */
    public function isDone()
    {
        if ($this->lastDataCount > 0){
            
            return false;
        }
        return true;
    }
    
    /**
     *  get rows by sql
     */
    public function executeSql()
    {
        $sql = $this->sql();
        $sql.=' LIMIT '.$this->pageSize;
        $sql.=' OFFSET '.($this->pageSize * $this->currentPage);
        $data = $this->getOriginDb()->createCommand($sql)->queryAll();
        
        return $data;
    }
    
    /**
     * sets total row count
     * 
     */
    public function setTotalCount()
    {
        if ($this->totalCount>0){
            return;
        }
        $sql = $this->sql();
        
        $this->totalCount = $this->getOriginDb()->createCommand("SELECT count(*) FROM ({$sql}) as query")->queryScalar();
    }
    
    /**
     * return total row count
     * 
     * @return integer
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * Действия до запуска query
     * @param array $inputData
     */
    public function beforeExecute(&$inputData = null)
    {
        if (isset($inputData['page'])){
            $this->currentPage = $this->targetPage = $inputData['page'];
            $this->bySinglePage = true;
        }
    }
    
    /**
     * 
     * @param type $inputData
     */
    public function isExecute(&$inputData = null)
    {
        return true;
    }
    
    /**
     * Действия после завершения операции
     * @param type $data
     * @param type $inputData
     */
    public function finishProcess(&$data, &$inputData = null)
    {
        
    }
    
    /**
     * Действия после завершения обработки 1 страницы данных
     * @param type $inputData
     */
    public function afterPageProcess(&$inputData = null)
    {
        
    }
    
    /**
     * Дейсвия при недоступности БД
     * 
     * @param array $data
     * @param \yii\db\Exception $exception
     */
    public function noDbConnectionExceptionActions($data, $exception)
    {
        
    }
    
    /**
     * returns array of prepared data
     * вносим необходимые изменения в данные
     * 
     * return array
     */
    abstract function prepareData(&$data);

    /**
     * Выполняем необходимые действия с данными
     */
    abstract function processData(&$data, &$inputData = null);
    
    /**
     * returns sql string
     * 
     * return string
     */
    abstract function sql();
}