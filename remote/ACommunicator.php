<?php
namespace core\remote;

use Yii;
use yii\base\Exception;
use common\filters\HttpBearerAuth;
use common\helpers\ConstHelper;
use core\remote\ABaseCommunicator;

/**
 * Базовый класс для запроса на удаленный ресурс
 * 
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class ACommunicator  extends ABaseCommunicator 
{
    public $accesBearerValue;
    
    public function init()
    {
        #инициализация основных хидеров
        $this->initBaseHeaders();
        $this->initBaseQuery();
    }

    private function getCurrentRoute() 
    {
        $c = Yii::$app->controller;
        if (! $c){
            return null;
        }
        $route = [];
        if($c->modules) {
            foreach (array_slice($c->modules, 1) as $module) {
                    $route[] = $module->id;
            }
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
        if (defined('PDS_USER_TOKEN_HEADER')){
            
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
            'Access:Bearer ' . $this->getAccessBearer(),
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
     * Возвращает токен дял атворизации системы 
     * @return string
     */
    public function getAccessBearer()
    {
        if ($this->accesBearerValue){
            return $this->accesBearerValue;
        }
        
        return Yii::$app->settings->byName(Yii::$app->id."-".ConstHelper::PDS_TOKEN);
    }
    
    /**
     * устанавливает токен для авторизации системы
     * @param string $accesBearerValue
     */
    public function setAccessBearer($accesBearerValue)
    {
        $this->accesBearerValue = $accesBearerValue;
    }
}

class ACommunicatorException extends Exception 
{
        public function getName()
        {
                return 'ACommunicator exception';
        }
}
