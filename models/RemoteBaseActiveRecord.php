<?php
namespace core\models;

use Yii;
use core\models\ActiveRecord;
use core\traits\CommunicatorTrait;
use yii\base\Exception;
use core\remote\ACommunicator;
use yii\web\UnauthorizedHttpException;
use yii\web\ForbiddenHttpException;

/**
 * базовая модель для данных которые частично хранятся на удаленном серваке
 * 
 * Если тру то запрос будет выполнен токльо удаленно
 * @property boolean $onlyRemote
 * Данные которые были получены с удаленного (удаленного в смысле на расстоянии, а не грохнутого) ресурса
 * @property array $remoteData
 * Дополнительные данные которые будем слать на удаленный сервак
 * [
 *      'info'=>[
 *          "file_number" : 321,
 *          "birth_certificate_series":"",
 *          "birth_certificate_number" : "1105646",
 *      ]
 * ]
 * @property array $extData
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class RemoteBaseActiveRecord extends ActiveRecord
{
    use CommunicatorTrait;
    
    public static $findLocally = false;
    protected static $onlyRemote = false;
    protected $remoteData;
    private $extData=[];
    
    /**
     * Если модель использует только удаленные данные то станлартный актив квери заменяется remote
     * @return ActiveQuery
     */
    public static function find($alias=null)
    {
        if (static::$findLocally){
            return static::findLocally($byInstitution, $alias);
        }
        $query = static::getQuery();
        
        return $query;
    }
    
    public static function findLocally($alias=null)
    {
        static::beforeFindModel();
        $query = Yii::createObject('core\queries\BaseActiveQuery',[get_called_class()]);
        static::afterFindModel();
        
        return $query;
    }
    
    /**
     * Получить экземпляр ActiveQuery
     * @return ActiveQuery
     */
    protected static function getQuery()
    {
        if (! static::$onlyRemote){
            return Yii::createObject('core\remote\queries\MixedRemoteBaseActiveQuery',[get_called_class()]);
        }
        
        return Yii::createObject('core\remote\queries\RemoteBaseActiveQuery',[get_called_class()]);
    }
    
    /**
     * Для вызова чистого сохранения базовой модели в дочерних классах
     * @param type $runValidation
     * @param type $attributeNames
     * @return type
     */
    public function clearSave($runValidation = true, $attributeNames = null)
    {
        return parent::save($runValidation, $attributeNames);
    }
    
    /**
     * @see yii\db\BaseActiveRecord
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if (! $this->initCommunicator($runValidation, $attributeNames)) {
            return false;
        }
        #если все пучком и не указано что сохранение должно быть только удаленным вызываем базовое сохранение
        if (! static::$onlyRemote){
            return $this->clearSave($runValidation, $attributeNames);
        }
        
        return true;
    }
    
    /**
     * Вот так я пихаю релеишены полученные из ремоута в модель
     * @see yii\db\BaseActiveRecord
     */
    public static function populateRecord($record, $row)
    {
        $remoteRelations = static::getRemoteModelRelationsMap();
        if ($remoteRelations && is_array($remoteRelations)){
            foreach ($remoteRelations as $name) {
                if (isset($row[$name])){
                    $record->populateRelation($name, $row[$name]);
                }
            }
        }
        parent::populateRecord($record, $row);
    }
    
    /**
     * @see yii\db\BaseActiveRecord
     */
    public function afterSave($insert, $changedAttributes) 
    {
        $this->setBackRemoteData();
        parent::afterSave($insert, $changedAttributes);
    }
    
    /**
     * Инициализируем комуникатор и делаем запрос
     * @param type $runValidation
     * @param type $attributeNames
     * @throws Exception
     */
    protected function initCommunicator($runValidation=true, $attributeNames=null)
    {
        if ($runValidation && !$this->validate($attributeNames)) {
            return false;
        }
        #берем комуникатор
        $communicator = $this->getCommunicator();
        if (! $communicator instanceof ACommunicator){
            throw new Exception(Yii::t('api', 'Комуникатор должен быть комуникатором!! Вот так вот!'));
        }
        $communicator->setPostfields($this->constructPostBody($communicator));
        #кидаем запрос
        $this->remoteData = $communicator->sendRequest();
        if (! $this->remoteData){
            throw new Exception(Yii::t('api', 'Запрос на удаленный сервер не вернул данных!'));
        }
        #если пришел запрет доступа к удаленному ресурсу просто возвращаем локальные данные
        if ($this->remoteData && isset ($this->remoteData['UNAUTHORISED']) && $this->remoteData['UNAUTHORISED']==true ){
            if(isset($this->remoteData['status']) && $this->remoteData['status'] == 403){
                throw new ForbiddenHttpException(Yii::t('api', $this->remoteData['message']));
            } else {
                throw new UnauthorizedHttpException(Yii::t('api', $this->remoteData['message']));
            }
        }
        $this->processPrimaryKey();
        $this->afterRemoteRequest($this->remoteData);
        return true;
    }
    
    /**
     * Пихаем удаленные данные назад в модель чтобы вернуть пользователю
     * @throws Exception
     */
    protected function setBackRemoteData()
    {
        $remoteFieldsNames = $this->getRemoteModelFieldsMap();
        if (!is_array($remoteFieldsNames)){
            throw new Exception(Yii::t('api', 'Ну нету массива с полями модели, которые лежат удаленно!!!'));
        }
        #на основе наших удаленных полей начинаем пихать обратно то что записали удаленно
        foreach ($remoteFieldsNames as $remoteFieldName) {
            if (! isset ($this->remoteData[$remoteFieldName]) ||  ! $this->remoteData[$remoteFieldName]){
                continue;
            }
            $this->{$remoteFieldName} = $this->remoteData[$remoteFieldName];
        }
    }
    
    /**
     * если первичные ключи записи не заполнены значит идет создание. 
     * в этом случае заполняем идентификаторы полученными значениями
     */
    protected function processPrimaryKey()
    {
        $primaryKeys = static::primaryKey();
        foreach ($primaryKeys as $primaryKey) {
            if (isset($this->remoteData[$primaryKey]) && ! $this->{$primaryKey}){
                $this->{$primaryKey} = $this->remoteData[$primaryKey];
            }
        }
    }
    
    /**
     * Действия после удаленного запроса
     * параметр сделан ссылкой для того чтобы при перегрузке метода в дочерних классах было понятно с чем работаем
     * @param array $remoteData
     */
    protected function afterRemoteRequest(&$remoteData)
    {
        
    }
    
    /**
     * Собирает тело поста для удаленного запроса
     * return array 
     */
    protected function constructPostBody(&$communicator)
    {
        $postBody=[];
        $remoteFieldsNames = $this->getRemoteModelFieldsMap();
        if (!is_array($remoteFieldsNames)){
            throw new Exception(Yii::t('api', 'Ну нету массива с полями модели, которые лежат удаленно!!!'));
        }
        #на основе наших удаленных полей начинаем собирать тело поста
        foreach ($remoteFieldsNames as $remoteFieldName) {
            $postBody[$remoteFieldName] =  $this->{$remoteFieldName};
            $doubleFields = $this->getDoubleWriteRemoteModelFieldsMap();
            if ($doubleFields && is_array($doubleFields) && in_array($remoteFieldName, $doubleFields)){
                //Значит поле надо сдублировать в локальную бд
            }else{
                $this->{$remoteFieldName} = null;
            }
        }
        #добавляем доп.данные
        foreach ($this->extData as $key=>$data) {
            $postBody[$key] = $data;
        }
        #добавляем первичные ключи для редактирования
        $idParam = null;
        $primaryKeys = static::primaryKey();
        foreach ($primaryKeys as $primaryKey) {
            if ($this->{$primaryKey} && ! $this->isNewRecord){
                $idParam.=$this->{$primaryKey}.',';
            }
            if (isset($postBody[$primaryKey]) && ! $this->isNewRecord){
                $idParam.=$postBody[$primaryKey].',';
            }
        }
        $idParam = trim($idParam, ',');
        if ($idParam){
            $communicator->setMethod('PUT');
            $communicator->setUrlIDParam($idParam);
        }
        #TODO добавляем в тело ид институшена
        //$postBody['institution_ids'][] = SystemSetting::$_institutionId;
        
        return $postBody;
    }
    
    /**
     * Добавляем дополнительные данные
     * @param string $key
     * @param mixed $data
     */
    public function addExtData($key, $data)
    {
        $this->extData[$key]=$data;
    }
    
    /**
     * Поля которые пишем в оба ресурса
     * 
     * return array
     */
    public function getDoubleWriteRemoteModelFieldsMap()
    {
        
    }
    
    /**
     * Получаем все поля которые находятся на удаленном ресурсе
     */
    abstract function getRemoteModelFieldsMap();  
    
    /**
     * прописываем все альясы для связей нужно для использования infoDecompose c ПДС
     */
    public static function getRemoteModelRelationsAliasesMap(){
        return null;
    }
    
    public static function getRemoteModelRelationsMap(){
        return null;
    }
    
    /**
     * Возвращает класс который отвечает за кастомный поиск
     * @return null||\common\search\base\BaseSearch
     */
    public static function getSearchClass()
    {
        return null;
    }
    
    public static function enableOnlyRemote()
    {
        static::$onlyRemote = true;
    }
    
    public static function disableOnlyRemote()
    {
        static::$onlyRemote = false;
    }
    
    public function isOnlyRemote()
    {
        return static::$onlyRemote;
    }
}
