<?php
namespace core\remote;

use Yii;
use yii\base\Exception;
use yii\base\Component;
use yii\helpers\Json;
use yii\base\InvalidParamException;

/**
 * Базовый класс для запроса на удаленный ресурс
 * 
 * @property curl $connection  - соединение
 * @property string $url       - адрес
 * @property array $urlExtra       - дополнительные параметры адреса
 * @property array $query       - адресгет параметры для запроса
 * @property string $method    - GET POST и т.д. кароче метод запроса
 * @property string $contentType - тип контента 
 * @property array $postfields - тело поста
 * @property array $option_defaults - дефолтные настрйоки
 * @property array $success_http_codes - http коды которые считаются успешными
 * @property array $headers - хидеры епта
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class ABaseCommunicator  extends Component 
{
    protected $connection;
    protected $url;
    protected $urlExtra = [];
    protected $query = [];
    protected $urlIDParam;
    protected $method = 'GET';
    protected $contentType = 'application/json';
    protected $postfields = null;
    protected $option_defaults = array(
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20
    ); 
    protected $success_http_codes = array(
        200,
        201,
        204
    ); 
    protected $headers = [];
    
    /**
     * Посылаем запрос
     * @return type
     * @throws ACommunicatorException
     */
    function sendRequest()
    {
        // Connect
        $this->connection = curl_init();
        if (!$this->connection) {
            throw new ABaseCommunicatorException(Yii::t('api', 'Не удалось инициализировать соединение'));
        }
        // установка параметров запроса
        if(isset($_GET['backend_debug'])){
            echo $this->url . $this->getUrlIDParam() . $this->getUrlExtra() . $this->getQuery() . PHP_EOL;
        }
        $options = array(
          CURLOPT_URL => $this->url . $this->getUrlIDParam(). $this->getUrlExtra() . $this->getQuery(),
          CURLOPT_CUSTOMREQUEST => $this->method, // GET POST PUT PATCH DELETE HEAD OPTIONS
          CURLOPT_POSTFIELDS => $this->postfields ? json_encode($this->postfields) : null ,
          CURLOPT_HTTPHEADER => $this->getHeaders(),
          CURLOPT_SSL_VERIFYHOST => 0,
          CURLOPT_SSL_VERIFYPEER => 0,
          CURLOPT_HEADER =>1
        );
        if(isset($_GET['backend_debug'])){
            print_r($this->getHeaders());
            print_r($options);
            print_r($this->method);
            print_r($this->postfields);
        }
        curl_setopt_array($this->connection,($options + $this->option_defaults));
        // send request and wait for responce
        $data =  curl_exec($this->connection);
        $header_size = curl_getinfo($this->connection, CURLINFO_HEADER_SIZE);
        $header = substr($data, 0, $header_size);
        $data = substr($data, $header_size);
        if ($data === false){
            $this->resolveCurlErrors($this->connection);
        }
        
        if(isset($_GET['backend_debug'])){
            print_r($data);
        //echo "------------------------------------";
//        echo "<br/>";
        }
        if (empty($data)) {
            $data=[];
        }
        $info = curl_getinfo($this->connection);
        curl_close($this->connection); // close cURL handlerRemoteBaseActiveQueryTrait.php
        $this->resolveResponse($data, $info);
        
        return $this->toArray($data);
    }
    
    /**
     * Разруливаем ответ от удаленного ресурса
     * @throws ACommunicatorException
     */
    public function resolveResponse(&$data, $curlInfo)
    {
        $message='Не удалось получить описание ошибки !';
        #просто пытаемся вытащить message
        try{
            $data = $this->toArray($data);
            $message = isset($data['message']) ? $data['message'] : Json::encode($data);
            $code = isset($data['code']) ? $data['code'] : 0;
        } catch (InvalidParamException $ex) {
        }
        if (in_array($curlInfo['http_code'], [401] )){
            throw new ABaseCommunicatorException($message,$curlInfo['http_code']);
        }
        #если удаленный ресурс не авторизовал не выбиваем ошибку и просто указываем что данных нет
        if ($curlInfo['http_code'] == 403 && $code == 603 ){
            $data['UNAUTHORISED']= true;
            return;
        }
        #если код ответа не входит в массив успешных шлем нахер и возвращаем ошибку
        if (! in_array($curlInfo['http_code'], $this->success_http_codes )){
            throw new ABaseCommunicatorException($message, $code);
        } 
    }
    
    /**
     * Разруливаем ответ curl если ответ false
     * @param type $connection
     * @throws ACommunicatorException
     */
    public function resolveCurlErrors($connection)
    {
            $errorCode = curl_errno($this->connection);
            switch ($errorCode) {
                case 28:
                    throw new ABaseCommunicatorException(Yii::t('api', 'Недоступен сервер авторизации.'),500);
            }
    }
    
    /**
     * делает ответ массивом
     * @param mixed $data
     * @return array
     */
    public function toArray($data)
    {
        if (is_string($data)){
            $data = Json::decode($data);
        }
        
        return $data;
    }
    
    /**
     * Устанавливает тело поста
     * @param type $postfields
     */
    public function setPostfields($postfields)
    {
        $this->postfields = $postfields;
    }
    
    /**
     * Добавить еще хидер
     * @param type $headers
     */
    public function addHeaders($headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }
    
    /**
     * геттер для массива установленных хидеров
     * @return type
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    
    /**
     * Устанавливает метод запроса
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }
    
    /**
     * Устанавливает параметры гет запроса
     * @param string $method
     */
    public function setQuery($query)
    {
        $this->query = array_merge($this->query, $query);
    } 
    
    /**
     * Удаляет ге т параметр
     * @param type $get
     */
    public function removeQuery($get)
    {
        if (isset($this->query[$get])){
            unset($this->query[$get]);
        }
    }
    
    /**
     * Получить гет параметры запроса
     * @return string
     */
    public function getQuery()
    {
        if ($this->query && is_array($this->query) && count($this->query)>0){
            return  '?'.http_build_query($this->query);
        }
        
        return null;
    }
    
    /**
     * Установить id для url
     * @param string $id
     */
    public function setUrlIDParam($id)
    {
        $this->urlIDParam = $id;
    }
    
    /**
     * Получить id для url
     */
    public function getUrlIDParam()
    {
        return $this->urlIDParam?'/'.$this->urlIDParam:null;
    }
    
    /**
     * Получение дополнительных параметров адреса
     * 
     * @return string
     */
    public function getUrlExtra()
    {
        $result = null;
        if(! $this->urlExtra || ! is_array($this->urlExtra)) {
            return $result;
        }
        foreach ($this->urlExtra as $param =>  $value) {
            $result .= "/{$param}/{$value}";
        }
        
        return $result;
    }

    /**
     * Добавление дополнительны параметров к адресу 
     * для поддержки формата objects/A1
     * 
     * @param array $params
     */
    public function addUrlExtra(array $params)
    {
        $this->urlExtra = array_merge($this->urlExtra, $params);
    }
    
    /**
     * Удаление дополнительного параметра адреса
     * 
     * @param string|null $key
     */
    public function removeUrlExtra($key = null)
    {
        if(! $key) {
            $this->urlExtra = [];
            
            return;
        }
        if(! isset($this->urlExtra[$key])) {
            return;
        }
        unset($this->urlExtra[$key]);
    }
}

class ABaseCommunicatorException extends Exception 
{
        public function getName()
        {
                return 'ABaseCommunicator exception';
        }
}