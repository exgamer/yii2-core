<?php
namespace core\remote;

use Yii;
use yii\base\Exception;
use yii\base\Component;
use common\filters\HttpBearerAuth;
use yii\helpers\Json;
use yii\base\InvalidParamException;
use common\helpers\ConstHelper;

/**
 * Базовый класс для запроса на удаленный ресурс
 * 
 * @property curl $connection  - соединение
 * @property string $url       - адрес
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
abstract class ACommunicator  extends Component 
{
    protected $connection;
    protected $url;
    protected $query = [];
    protected $urlIDParam;
    protected $method = 'GET';
    protected $contentType = 'application/json';
    protected $postfields = null;
    protected $option_defaults = array(
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 2
    ); 
    protected $success_http_codes = array(
        200,
        201,
        204
    ); 
    private $headers = null;
    
    public function init()
    {
        #инициализация основных хидеров
        $this->initBaseHeaders();
        $this->initBaseQuery();
    }

    private function getCurrentRoute() {
		$c = Yii::$app->controller;
        if (! $c){
            return null;
        }
		$route = [];
		foreach (array_slice($c->modules, 1) as $module) {
			$route[] = $module->id;
		}
		$route[] = $c->id;
		$route[] = $c->action->id;
		return implode('/', $route);
	}

    /**
     * Получение текущей роли с хидера, если это консоль то будет superadmin
     *
     * @return string
     */
    public function getRoleHeader() {
        if (defined('PDS_ROLE_HEADER')){
            return PDS_ROLE_HEADER;
        }
        if (Yii::$app->controller instanceof \yii\console\Controller){
            return 'superadmin';
        } else {
            return Yii::$app->request->getHeaders()->get('CurrentRole');
        }
    }
      
    /**
     * Получить авторизационный токен
     * @return string 
     */
    public function getAuthToken()
    {
        // used if application has session component
        if (Yii::$app->has('session')){
            $session = Yii::$app->session;
            $PDS_USER_TOKEN_HEADER = $session->get('PDS_USER_TOKEN_HEADER');
            if ($PDS_USER_TOKEN_HEADER){
                return $PDS_USER_TOKEN_HEADER;
            }
        }
        // used for acess from console application
        if (Yii::$app->controller instanceof \yii\console\Controller && defined('PDS_USER_TOKEN_HEADER')){
            
            return PDS_USER_TOKEN_HEADER;
        }
        // used for acess from console application
        if (Yii::$app->controller instanceof \yii\console\Controller){
            
            return null;
        }
        
        return HttpBearerAuth::getAuthHeader();
    }
    
    /**
     * Установить авторизационный токен ПДС
     * @param string $token 
     * @param boolean $session - если установлено false пишем в глобальную переменную а не в сессию 
     */
    public static function setAuthToken($token, $session=true)
    {
        if ($session){
            $session = Yii::$app->session;
            $session->set('PDS_USER_TOKEN_HEADER', $token);
        }
        
        define('PDS_USER_TOKEN_HEADER', $token);
    }
    
    
    /**
     * Инициализируем основные хидеры
     */
    public function initBaseHeaders()
    {
        $this->headers = [
            'Content-Type:'.$this->contentType,
            'Authorization:Bearer '.$this->getAuthToken(),
            //'Access:Bearer ' . Yii::$app->settings->byName("app-pedat-".ConstHelper::PDS_TOKEN),
            'Access:Bearer ' . Yii::$app->settings->byName(Yii::$app->id."-".ConstHelper::PDS_TOKEN),
            'Access-Route: ' . $this->getCurrentRoute(),
//            'Access-Role: ' . 'superadmin'
            'Access-Role: ' . $this->getRoleHeader()
        ];
    }
    
    /**
     * добавляем стандартные параметры запроса типа пагинация и т.п.
     */
    public function initBaseQuery()
    {
        $c = Yii::$app->controller;
        #если контроллер консольный ниче не делем
        if ($c instanceof \yii\console\Controller){
            return;
        }
        if (! isset($c->reservedParams)){
            return;
        }
        foreach ($c->reservedParams as $param) {
            if (Yii::$app->request->get($param)){
                $this->query[$param] = Yii::$app->request->get($param);
            }
        }
    }
    
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
            throw new ACommunicatorException(Yii::t('api', 'Не удалось инициализировать соединение'));
        }
        // установка параметров запроса
        if(isset($_GET['backend_debug'])){
            echo $this->url.$this->getUrlIDParam().$this->getQuery() . PHP_EOL;
        }
        $options = array(
          CURLOPT_URL => $this->url.$this->getUrlIDParam().$this->getQuery(),
          CURLOPT_CUSTOMREQUEST => $this->method, // GET POST PUT PATCH DELETE HEAD OPTIONS
          CURLOPT_POSTFIELDS => $this->postfields?json_encode($this->postfields):null ,
          CURLOPT_HTTPHEADER => $this->getHeaders(),
          CURLOPT_SSL_VERIFYHOST => 0,
          CURLOPT_SSL_VERIFYPEER => 0
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
            throw new ACommunicatorException($message,$curlInfo['http_code']);
        }
        #если удаленный ресурс не авторизовал не выбиваем ошибку и просто указываем что данных нет
        if ($curlInfo['http_code'] == 403 && $code == 603 ){
            $data['UNAUTHORISED']= true;
            return;
        }
        #если код ответа не входит в массив успешных шлем нахер и возвращаем ошибку
        if (! in_array($curlInfo['http_code'], $this->success_http_codes )){
            throw new ACommunicatorException($message, $code);
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
                    throw new ACommunicatorException(Yii::t('api', 'Недоступен сервер авторизации.'),500);
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
}

class ACommunicatorException extends Exception 
{
        public function getName()
        {
                return 'ACommunicator exception';
        }
}
