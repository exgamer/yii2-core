<?php
namespace core\analytics;

/**
 * Base class for long term and non dynamic analytics collect
 * 
 * @property \core\analytics\Query $queryClass
 * @property date $dateFrom
 * @property date $dateTo
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class Collector
{
    public $queryClass;
    public $dateFrom;
    public $dateTo;
    public $queries;

    function  __construct($dateFrom = null, $dateTo = null, $queries = [],$params = [])
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->queries = $queries;
        $this->setUp($params);
    }
    
    /**
     * Выбиваем данные
     * 
     * @return boolean
     */
    public function collect()
    {
        $queryClass = $this->queryClass;
        
        return $queryClass::query($this->dateFrom, $this->dateTo, $this->queries);
    }
    
    /**
     * для доп телодвижений
     * 
     * @param array $params
     */
    public function setUp(&$params)
    {
        
    }
}
