<?php
namespace core\traits;

use Yii;
use yii\base\Exception;
use core\remote\ACommunicator;
use yii\web\Request;

/**
 * Базовые методы для remoteactivequery
 * 
 * @property array $remoteWhere     - параметры для запроса на удаленный ресурс
 * @author CitizenZet <exgamer@live.ru>
 */
trait RemoteBaseActiveQueryTrait
{
    public $remoteActiveRecordClass = 'core\models\RemoteBaseActiveRecord'; 
    
    public $splittedRemoteActiveRecordClass = 'core\models\SplittedBaseActiveRecord';
    
    protected $remoteWhere=[];
    
    /**
     * Метка, которая указывает есть ли в условиях поиск по remote fields 
     * (используется RemoteDataProvider чтобы понимать делать первым запрос на remote или locale)
     * @var boolean 
     */
    private $searchByRemoteFields=false;
    
    /**
     * Если оно заполнено то смержить эти данные 
     * @var null|array 
     */
    public $remoteData;
    
    /**
     * Если оно заполнено то смержить эти данные 
     * @var null|array 
     */
    public $localData;
    
    /**
     * Поиск по remote полям на обоих ресурсах
     * @deprecated
     * @var boolean 
     */
    protected $doubleSearch = false;
    
    /**
     * Делаем различные неприличные вещи перед запросом
     */
    public function beforeQuery()
    {
        $this->splitWhere();
        $this->buildSearchParams();
        $this->setExpand();
        /**
         * TODO 
         * Убоал потому что из за этого могу тне находиться записи
         */
        //if (SystemSetting::$_institutionId){
            //$this->remoteWhere['institution_ids'][] = SystemSetting::$_institutionId;
        //}
    }

    /**
     * Отделяем параметры локльного запроса от удаленного
     */
    public function splitWhere()
    {
        $model = $this->getModel();
        #если условия нет ниче не надо делать
        if (! $this->where){
            return;
        }
        #поддерживается пока только массив
        if (! is_array($this->where)){
            return;
        }
        #TODO костыль будет время отрефакторить
        if (isset($this->where[0])){
            if ($this->where[0]=='in'){
                $this->remoteWhere[$this->where[1][0]] = implode(',', $this->where[2]);
            }else{
                foreach ($this->where as $key=>$condition) {
                    if (is_array($condition)){
                        $this->buildWhere($condition,$model,$key);
                    }
                }
            }
            return;
        }

        $this->buildWhere($this->where,$model);
    }
    
    /**
     * Добавялем в запрос параметры из Search Модели
     */
    public function buildSearchParams()
    {
        $modelClass = $this->modelClass;
        $searchClass = $modelClass::getSearchClass();
        if (! $searchClass){
            return;
        }
        $paramsMap = $searchClass::getRemoteParamsMap();
        if (! $paramsMap || !is_array($paramsMap)){
            return;
        }
        if(! Yii::$app->getRequest() instanceof Request){
            $qParams = [];
        } else {
            $qParams = Yii::$app->getRequest()->queryParams;
        }
        foreach ($paramsMap as $param) {
            if (isset($qParams[$param]) && $qParams[$param]){
                $this->setSearchByRemoteFields(true);
                $this->remoteWhere[$param] = $qParams[$param];
            }
        }   
    }
    
    /**
     * Устанавливаем экспанды
     */
    public function setExpand()
    {
        $exp = null;
        if (Yii::$app->request instanceof Request){
            $exp = Yii::$app->request->get('expand');
            if($exp != null){
                $temp = [];
                $exp = explode(',',$exp);
                foreach ($exp as  $value) {
                    $temp[]=trim($value);
                }
                $exp = $temp;
            }
        }
        $expand=null;
        if ($this->with && is_array($this->with)){
            $model = new  $this->modelClass();
            $remoteRelations = $model::getRemoteModelRelationsMap();
            if ($remoteRelations && is_array($remoteRelations)){
                foreach ($remoteRelations as $name) {
                    if (isset($this->with[$name]) ||  ($exp && in_array($name, $exp))){
                        $expand.=$name.',';
                    }
                }
            }
        }
        if ($expand){
            $this->remoteWhere['expand'] = trim($expand,',');
        }
    }
    
    /**
     * сбор обычного where
     * @param type $where
     * @throws Exception
     */
    protected function buildWhere(&$where,$model,$parentKey=null)
    {
        #подрезаем алиасы из запроса и убираем лишнее из where
        foreach ($where as $key => $value) {
            $cleanKey = str_replace($this->alias.".", '', $key);
            # в запрос добавляем только поля которые храним на удаленном ресурсе
            if (in_array($cleanKey, $model->getRemoteModelFieldsMap()) || $cleanKey==ID_FIELD){
                $this->remoteWhere[$cleanKey] = $value;
            }
            
            #TODO для совместимости не удаляем поля из поиска локалоьной модели, т.к. данные могут быть и в ней
            #если модель смешанная и отключен поиск по обоим ресурсам , то удаляем из основного запроса лишние условия (исключение поле ID)
            if (! $this->doubleSearch && in_array($cleanKey, $model->getRemoteModelFieldsMap()) && $model instanceof $this->remoteActiveRecordClass && $cleanKey != ID_FIELD){
                $this->setSearchByRemoteFields(true);
                if ($parentKey){
                    unset($this->where[$parentKey][$key]);
                    if (count($this->where[$parentKey])==0){
                        unset($this->where[$parentKey]);
                    }
                }else{
                    unset($where[$key]);
                }
            }
            #если модель не SplittedBaseActiveRecord или не RemoteBaseActiveRecord то это безобразие
            if (! $model instanceof $this->splittedRemoteActiveRecordClass && ! $model instanceof $this->remoteActiveRecordClass){
                throw new Exception(Yii::t('api', 'Алярм модель должна быть наследником RemoteBaseActiveRecord или SplittedBaseActiveRecord!'));
            }
        } 
    }
    
    
    
    /**
     * Получить данные с удаленного сервака
     * и разрулить
     */
    public function getData()
    {
        if ($this->remoteData){
            return $this->remoteData;
        }
        $model = $this->getModel();
        $communicator = $model->getCommunicator();
        if (! $communicator instanceof ACommunicator){
            throw new Exception(Yii::t('api', 'Комуникатор должен быть комуникатором!! Вот так вот!'));
        }
        $communicator->setMethod('GET');
        # а то потому что пока принимаем только массив
        if ($this->remoteWhere && is_array($this->remoteWhere)){
            $communicator->setQuery($this->remoteWhere);
        }
        if ($this->searchByRemoteFields){
            $communicator->setQuery(['per-page'=>1000]);
        }
        $remoteData = $communicator->sendRequest();
        //индесируем удаленные данные по первичному ключу
        $this->indexBy=function($row) use ($model){
            return $this->getIndexKeyByPrimary($model, $row);
        };
        if(! $remoteData){
            /**
             * Если это сплит модель, и пдс вернул ничего, то ищем в локальной бд
             */
            if ($model instanceof $this->splittedRemoteActiveRecordClass){
                foreach ($this->remoteWhere as $attr=>$value) {
                    if ($model->hasAttribute($attr)){
                        $this->andWhere([$attr=>$value]);
                    }
                }
            }
            $this->indexBy=null;
            return $remoteData;
        }
        #если пришел запрет доступа к удаленному ресурсу просто возвращаем локальные данные
        if (isset ($remoteData['UNAUTHORISED']) && $remoteData['UNAUTHORISED']==true ){
            if ($model->isOnlyRemote()){
                $result = [];
            }else{
                $result = $remoteData;
            }
        } else {
            $result = $this->createModels($remoteData);
            /**
             * Если включен поиск по ремоут полям берем полученные ключи со значениями и подставляем в запрос
             */
            if ($this->isSearchByRemoteFields()){
                $this->setRemoteIds($result);
            }
        }
        $this->indexBy=null;

        return $result;
    }
    
    /**
     * Добавить в запрос id из удаленного запроса
     * @param type $result
     */
    public function setRemoteIds($result = null)
    {
        if (! $result){
            $result = $this->remoteData;
        }
        $ids = array_keys($result);
        $keyData = $this->getKeyData($ids);
        foreach ($keyData as $attribute => $valueString) {
            $vArray= explode(',', $valueString);
            $this->andWhere([$attribute=>$vArray]);
            unset($vArray);
        }
        unset($keyData);
    }
    
    /**
     * объединяем данные
     * @param type $localData
     * @param type $remoteData
     * @return type
     */
    public function mergeData($localData, $remoteData)
    {
        $model = $this->getModel();
        if (! $localData){
            $localData = [];
        }
        #если пришел запрет доступа к удаленному ресурсу просто возвращаем локальные данные
        if ($remoteData && isset ($remoteData['UNAUTHORISED']) && $remoteData['UNAUTHORISED']==true ){
            return $localData;
        }
        #если модель SplittedBaseActiveRecord просто мерджим данные
        if ($model instanceof $this->splittedRemoteActiveRecordClass){
            return array_merge($localData, $remoteData);
        }
        #иначе обрабатываем
        foreach ($localData as $key =>$data) {
            #получаем строку с ключем
            $iKey = $this->getIndexKeyByPrimary($model, $data);
            if (isset($remoteData[$iKey])){
                $rData = $remoteData[$iKey];
                #мерджим релеишены
                $remoteRelations = $model::getRemoteModelRelationsMap();
                if ($remoteRelations && is_array($remoteRelations)){
                    foreach ($remoteRelations as $name) {
                        #мержим релеишены как массивы
                        if (is_array($data)){
                            $lR = $localData[$key][$name];
                            $rR = $rData[$name];
                            $this->mergeRelations($lR, $rR, $name);
                            $localData[$key][$name] = $lR;
                        }else{
                            #мержим релеишены как объекты
                            $lR = $localData[$key]->getRelatedRecords();
                            $rR = $rData->getRelatedRecords();
                            $this->mergeRelations($lR, $rR, $name);
                            $localData[$key]->populateRelation($name, $lR[$name]);
                        }
                    }
                }
                #мержим поля
                foreach ($model->getRemoteModelFieldsMap() as $field) {
                    if (is_array($data)){
                        $localData[$key][$field] = $rData[$field];
                    }else{
                        $localData[$key]->{$field} = $rData->{$field};
                    }
                }
            }else{
                #убираем unset потому что в обоих базах поля будут одинаковые и результат запроса тоже, в локальной не должно быть лишних записей
                #если нет соответсвующих данных извне, следовательно не найдено соответсвие по поиску (например пофамилии), вырезаем нахер
                #TODO один момент, если надо будет показать данные независимо от запроса, например у кого то ФИО есть и в нашей БД то эта запись не будет показана
                if (! $this->doubleSearch){
                    unset($localData[$key]);
                }
            }
        }

        return $localData;
    }
    
    /**
     * объединяем релеишены
     * @param array $localRealtions
     * @param array $remoteRelations
     * @param string $relationName
     */
    protected function mergeRelations(&$localRealtions,$remoteRelations, $relationName)
    {
        $remoterelation=[];
        if (isset($remoteRelations[$relationName])){
            $remoterelation = $remoteRelations[$relationName];
        }
        $localrelation=[];
        if (! empty($localRealtions[$relationName])){
            $localrelation = $localRealtions[$relationName];
        }
        $localRealtions[$relationName] = array_merge($localrelation, $remoterelation);
    }
    
    /**
     * Вернуть строку со сгенерированным индексом по первичному ключу
     * @param type $model
     * @param type $row
     * @return type
     */
    protected function getIndexKeyByPrimary($model, $row, $separator='-')
    {
            $key = null;
            $primaryKeys = $model::primaryKey();
            foreach ($primaryKeys as $primaryKey) {
                if (is_array($row)){
                    if (isset($row[$primaryKey])){
                        $key.= $row[$primaryKey].$separator;
                    }
                }
                if (is_object($row)){
                    if ($row->{$primaryKey}){
                        $key.= $row[$primaryKey].$separator;
                    }
                }
            }
            $key = trim($key, $separator);
            
            return $key;
    }
    
    /**
     * Получить массив с полями из первичного ключа со значениями
     * @param integer[] $ids
     * @return array
     */
    public function getKeyData($ids)
    {
        $result=[];
        $model = $this->getModel();
        #узнаем первичныt ключи модели
        $primaryKeys = $model::primaryKey();
        #если первичный ключ один сразу добавляем его в параметры
        if (count($primaryKeys)==1){
            $result[$primaryKeys[0]]=implode(',', $ids);
        }else{
            #собираем запрос с параметрами по составному первичному ключу
            $keyDataArray=[];
            foreach ($ids as $id) {
                $keyVals = explode('-', $id);
                foreach ($keyVals as $key =>$value) {
                    $keyDataArray[$key][]=$value;
                }
            }
            foreach ($primaryKeys as $key=>$primaryKey) {
                if (! isset($keyDataArray[$key])){
                    continue;
                }
                $array = array_unique($keyDataArray[$key]);
                $result[$primaryKey]=implode(',', $array);
            }
        }
        
        return $result;
    }
    
    /**
     * Установить метку поиска по remote fields
     * @param type $searchByRemoteFields
     */
    public function setSearchByRemoteFields($searchByRemoteFields)
    {
        $this->searchByRemoteFields=$searchByRemoteFields;
    }
    
    /**
     * Включен ли поиск по remote fields
     * @return boolean
     */
    public function isSearchByRemoteFields()
    {
        return $this->searchByRemoteFields;
    }
    
    /**
     * Получить экземпляр модели
     * @return ActiveRecord
     */
    protected function getModel()
    {
        return new $this->modelClass();
    }
    
    public function setRemoteActiveRecordClass($remoteActiveRecordClass)
    {
        $this->remoteActiveRecordClass = $remoteActiveRecordClass;
    }
    
    public function setSplittedRemoteActiveRecordClass($splittedRemoteActiveRecordClass)
    {
        $this->splittedRemoteActiveRecordClass = $splittedRemoteActiveRecordClass;
    }
}

