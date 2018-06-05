<?php
namespace core\analytics;

/**
 * Base class for long term and non dynamic analytics collect
 * 
 * @property date    $dateFrom    - начало периода сбора инфы
 * @property date    $dateTo      - конец периода сбора инфы
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class NoSqlQuery
{
    public $dateFrom;
    public $dateTo;

    function  __construct($dateFrom = null, $dateTo = null, $subQueries = null, &$inputData = null)
    {
        $this->setPeriod($dateFrom, $dateTo);
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
     * Выполнить
     */
    abstract function execute(&$inputData = null);
}
