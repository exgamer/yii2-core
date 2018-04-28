<?php
namespace core\analytics;

use Yii;
use core\controllers\BaseCommandController;
use yii\helpers\VarDumper;
use core\helpers\StringHelper;

/**
 * Базовый контроллер для сбора аналитических данных
 * 
 * @author CitizenZet
 */
abstract class BaseCollectorController extends BaseCommandController
{   
    /**
     * Название класса коллектора
     * 
     * @var string / null
     */
    public $collector = null;
    
    /**
     * Подключаемые подзапросы
     * @var string 
     */
    public $queries = null;
    /**
     * начало периода сбора
     * @var date
     */
    public $dateFrom = null;
    
    /**
     *завершение периода сбора
     * @var date 
     */
    public $dateTo = null;
    
    public $page = null;
    
    /**
     * Списко коллекторов
     * @var array
     */
    protected $collectorList = [];
    
    /**
     * Списко подзапросов
     * @var array
     */
    protected $queryList = [];
    
    public $collectorsNamespacePath = 'console\modules\analytics\collectors';
    
    public function options($actionID)
    {
        return ['collector','dateFrom','dateTo','queries','page'];
    }
    
    /**
     * Импорт данных
     */
    public function actionIndex()
    {
        if(! is_array($this->collectorList)){
            $this->outputDone(Yii::t('console','Список коллекторов пуст.'));
        }
        foreach ($this->collectorList as $collector) {
            $this->runCollector($collector);
        }
    }

    /**
     * Запуск сборщика
     * 
     * @param string $collector
     * @param string $queries
     * @return boolean
     */
    protected function runCollector($collector, $queries = null)
    {
        $className = StringHelper::getClassNameWithoutNamespace($collector);
        if($className){
            $collector = $className;
        }
        $collectorClass = $this->collectorsNamespacePath . '\\' . $collector;
        if (! class_exists($collectorClass)) {
            $this->outputDone(Yii::t('console','{collector} класс не определен.', ['collector' => $collector]));
            
            return false;
        }
        if ($this->queries){
            $this->queryList = explode(',', $this->queries);
        }
        $model = $this->getCollector($collectorClass);

        $timeStart = new \DateTime();
        $this->outputSuccess(Yii::t('console','{collector} запущен... (Время: {time})',
            [
                'collector' => $collector,
                'time' => $timeStart->format('H:i:s'),
            ])
        );
        if (! $model->collect()) {
            VarDumper::dump($model->getErrors());
        }

        $timeEnd = new \DateTime();
        $execTime = $timeStart->diff($timeEnd);
        $this->outputSuccess(Yii::t(
            'console',
            '{collector} завершил сбор данных... (Start: {timeStart}, End: {timeEnd}, Execution time: {execTime})',
            [
                'collector' => $collector,
                'timeStart' => $timeStart->format('H:i:s'),
                'timeEnd' => $timeEnd->format('H:i:s'),
                'execTime' => $execTime->format('%H:%I:%S'),
            ])
        );
    }
    
    /**
     * Возвращает коллектор
     * 
     * @return Collector $collector
     */
    public function getCollector($collectorClass)
    {
        return new $collectorClass($this->dateFrom, $this->dateTo, $this->queryList,['page'=>$this->page]);
    }
    
    /**
     * Добавление коллектора в лист
     * 
     * @param string $connector
     */
    protected function pushCollectorList($collector)
    {
        $this->collectorList[] = $collector;
    }
}