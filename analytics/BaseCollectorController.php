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
     * начало периода сбора
     * @var date
     */
    public $dateFrom = null;
    
    /**
     *завершение периода сбора
     * @var date 
     */
    public $dateTo = null;
    
    /**
     * Списко коллекторов
     * @var array
     */
    protected $collectorList = [];
    
    public $collectorsNamespacePath = 'console\modules\analytics\collectors';
    
    public function options($actionID)
    {
        return ['collector','dateFrom','dateTo'];
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
     * @return boolean
     */
    protected function runCollector($collector)
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
        $model = new $collectorClass($this->dateFrom, $this->dateTo);
        $this->outputSuccess(Yii::t('console','{collector} запущен...', ['collector' => $collector]));
        if (! $model->collect()) {
            VarDumper::dump($model->getErrors());
        }
        $this->outputSuccess(Yii::t('console','{collector} завершил сбор данных...', ['collector' => $collector]));
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
