**Классы для сбора аналитической информации**


1. для начала работы должны быть созданы классы описанные в REPLICATION_README.md в корне проекта

2. Создаем класс наследник от core\analytics\MongoQuery

==================================Пример========================================
<?php
namespace console\modules\analytics\queries;

use yii\helpers\ArrayHelper;
use yii\db\Connection;
use core\analytics\MongoQuery;
use console\modules\analytics\queries\traits\InstitutionQueryAdditionalDataTrait;
use console\modules\analytics\queries\traits\BilimalGeneralDbConnectionTrait;

/**
 * Base class for long term and non dynamic analytics collect
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
class InstitutionQuery extends MongoQuery
{
    use BilimalGeneralDbConnectionTrait;
    use InstitutionQueryAdditionalDataTrait;
    
    public $targetModelClass = 'common\models\analytics\organization\Institution';
    public $targetReplicationModelClass = 'common\replication\mongo\models\organization\Institution';
    
    public $subQueries = ['DivisionQuery'];
        
    /**
     * @see \console\modules\analytics\collectors\base\BaseQuery
     */
    public function prepareData(&$data)
    {
        $data['institution_id'] = $data['id'];
        $data['institution_type'] = $data['type'];
        $data['parent_institution_id'] = $data['parent_id'];
        $data['language'] = ArrayHelper::toPhpArray($data['language']);
        //$institution_db_connection = $this->getInstitutionDbConnection($data);
        $data['db_connection'] = $this->getInstitutionDbConnection($data);
        //$this->getDivisionInfo($data['institution_id'], $institution_db_connection,  $data);
        //$institution_db_connection->close();
        //print_r($data);
    }
    
    /**
    * @see \console\modules\analytics\collectors\base\BaseQuery
    */
    public function finishProcess(&$data, &$inputData = null)
    {
        //TODO закрыть коннекты
        $data['db_connection']->close();
    }
    
    /**
     * Получить Db connection организации
     * @param array $data
     * @return Connection
     */
    public function getInstitutionDbConnection($data)
    {
        $domain = ArrayHelper::getValue($data, 'domain', 'db.verter.vpn');
        $dbName = ArrayHelper::getValue($data, 'db_name', 'dev_bilimal');
        $institution_db_connection = new Connection();
        $institution_db_connection->dsn = "pgsql:host={$domain};dbname={$dbName}";
        $institution_db_connection->username = ArrayHelper::getValue($data, 'db_user', 'bilimal');
        $institution_db_connection->password = ArrayHelper::getValue($data, 'db_password', 'bilimal');
        $institution_db_connection->charset = 'utf8';
        
        return $institution_db_connection;
    }
    
    /**
     * @see \console\modules\analytics\collectors\base\Query
     */
    public function getReplicationModelSearchParams($data)
    {
        return [
            'institution_id'=> (int) $data['institution_id'],
        ];
    }

    /**
     * @see \console\modules\analytics\collectors\base\BaseQuery
     */
    public function sql()
    {
        return "SELECT * FROM organization.institution WHERE is_deleted = 'f' AND status = 1";
    }
    
    /**
     * @see \console\modules\analytics\collectors\base\Query
     */
    public function showMessage($isUpdate, $model)
    {
        if ($isUpdate){
            echo $this->targetModelClass." UPDATING row with institution_id = {$model->institution_id} FOR {$model->date} ... ".PHP_EOL;
        }else{
            $date = $model->date ? $model->date : $this->dateTo;
            echo $this->targetModelClass." CREATING row with institution_id = {$model->institution_id} FOR {$date} ... ".PHP_EOL;
        }
    }
}

================================================================================

3. Создаем класс наследник от console\modules\analytics\collectors\base\Collector

==========================Пример================================================
<?php
namespace console\modules\analytics\collectors;

use Yii;
use console\modules\analytics\collectors\base\Collector;

/**
 * Выбиваем данные по заведениям
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
class InstitutionCollector extends Collector
{
    public $queryClass = 'console\modules\analytics\queries\InstitutionQuery';

}
================================================================================

4. При необходимости создаем насслденик класса core\analytics\BaseQuery
   Класс принимает массив который нужно заполнить собранными данными

==============================Пример============================================
Треит подключен к основному Query который был описан выше
и вызывает DivisionQuery для сбора информации по классам
<?php
namespace console\modules\analytics\queries\traits;

use console\modules\analytics\queries\DivisionQuery;

/**
 * Треит для работы с дополнительными данными заведений
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
trait InstitutionQueryAdditionalDataTrait
{
    /**
     * Получить данные по классам
     * 
     * @param integer $institution_id
     */
    public function getDivisionInfo($institution_id, &$data)
    {
        $query = new DivisionQuery($institution_id, $this->dateFrom, $this->dateTo);
        $query->execute($data);
    }
}


<?php
namespace console\modules\analytics\queries;

use core\analytics\BaseQuery;
use common\models\analytics\organization\Institution;

/**
 * Собираем данные по классам
 * 
 * @property integer $institution_id - id заведения
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
class DivisionQuery extends BaseQuery
{  
    private $institution_id;
            
    function  __construct($dateFrom = null, $dateTo = null, $subQueries = null, &$inputData = null)
    {
        parent::__construct($dateFrom, $dateTo, $subQueries, $inputData);
        $this->institution_id= $inputData['institution_id'];
        $this->setOriginDb($inputData['db_connection']);
    }
    
    /**
     * @see \console\modules\analytics\collectors\base\BaseQuery
     */
    public function prepareData(&$data)
    {

    }

    public function processData(&$data, &$inputData = null)
    {
        Institution::addDivisionInfo($inputData, $data);
    }

    /**
     * @see \console\modules\analytics\collectors\base\BaseQuery
     */
    public function sql()
    {
        //выбираем классы со значениями grade и caption на указанной временной период
        // В зависимости от того выборка за текущую дату или нет выставляем LEFT или INNER JOIN
        $joinType = 'INNER';
        $query_year = self::getCurrentAcademicYear($this->dateTo);
        $current_year = self::getCurrentAcademicYear(date('Y-m-d'));
        if ($query_year == $current_year){
            $joinType = 'LEFT';
        }
        
        return "
            SELECT d.id,
            d.language,d.institution_id,
            (CASE WHEN dg.caption IS NOT NULL  THEN dg.caption
                        ELSE d.caption
            END) as caption,
            (CASE WHEN dg.grade IS NOT NULL  THEN dg.grade
                        ELSE d.grade
            END) as grade
            FROM organization.division  d
            {$joinType} JOIN organization.division_period dg ON dg.division_id = d.id AND '{$this->dateFrom}'>=dg.from_ts AND '{$this->dateTo}'<=dg.to_ts AND dg.is_deleted = 'f' AND dg.status = 1
            WHERE d.is_deleted = 'f' AND d.status = 1 AND (d.parent_id IS NULL OR d.parent_id = 0) AND d.institution_id = {$this->institution_id} 
            GROUP BY d.id,dg.caption,dg.grade    
        ";
    }
}




================================================================================

5. В запросах где данные зависят от дат в методе sql необходимо предусмотреть выборку по датам